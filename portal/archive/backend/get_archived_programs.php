<?php
// PERFORMANCE: Add caching support
include __DIR__ . '/../../home/backend/cache_helper.php';
$cache = new SimpleCache(__DIR__ . '/../../home/backend/cache');

// Check cache first (5 minute TTL)
// v3: Fixed image path detection for hosted environment
$cache_key = 'archived_programs_list_v3';
$cached_data = $cache->get($cache_key);

if ($cached_data !== null) {
    // Serve from cache
    header('Content-Type: application/json');
    header('X-Cache: HIT');
    echo $cached_data;
    exit();
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

try {
    // Use __DIR__ for absolute path
    $db_path = __DIR__ . '/../../home/db.php';
    
    if (!file_exists($db_path)) {
        throw new Exception('Database configuration file not found');
    }
    
    include $db_path;

    // Check database connection
    if ($conn->connect_error) {
        echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
        exit();
    }

    // OPTIMIZED: Query to get archived programs with images in ONE query
    // This eliminates the N+1 query problem using GROUP_CONCAT for both archived and fallback images
    $sql = "SELECT 
            pa.id, pa.original_program_id, pa.program_name, pa.project_titles, d.department_name as department, 
            pa.location, pa.start_date, pa.end_date, pa.status, pa.max_students, pa.description, pa.sdg_goals, 
            pa.created_at, pa.updated_at,
            GROUP_CONCAT(DISTINCT ia.archive_image_id ORDER BY ia.archive_image_id ASC SEPARATOR '||') as archive_image_ids,
            GROUP_CONCAT(DISTINCT COALESCE(ia.image_desc, 'Program image') ORDER BY ia.archive_image_id ASC SEPARATOR '||') as archive_image_descs,
            GROUP_CONCAT(DISTINCT i.image_id ORDER BY i.image_id ASC SEPARATOR '||') as fallback_image_ids,
            GROUP_CONCAT(DISTINCT COALESCE(i.image_desc, 'Program image') ORDER BY i.image_id ASC SEPARATOR '||') as fallback_image_descs
        FROM programs_archive pa
        LEFT JOIN departments d ON pa.department_id = d.department_id
        LEFT JOIN images_archive ia ON pa.id = ia.archive_program_id
        LEFT JOIN images i ON pa.original_program_id = i.program_id
        GROUP BY pa.id, pa.original_program_id, pa.program_name, pa.project_titles, d.department_name,
                 pa.location, pa.start_date, pa.end_date, pa.status, pa.max_students, pa.description, 
                 pa.sdg_goals, pa.created_at, pa.updated_at
        ORDER BY pa.updated_at DESC";

    $result = $conn->query($sql);

    // Check for SQL errors
    if (!$result) {
        echo json_encode(['error' => 'SQL Error: ' . $conn->error]);
        exit();
    }

    // Check if records exist
    if ($result->num_rows > 0) {
        $programs = [];
        
        while($row = $result->fetch_assoc()) {
            $images = [];

            // First try archived images (preferred)
            if (!empty($row['archive_image_ids'])) {
                $image_ids = explode('||', $row['archive_image_ids']);
                $image_descs = explode('||', $row['archive_image_descs']);
                
                // Detect if local (has /Etracker in path) or hosted (no /Etracker)
                $is_local = isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/Etracker/') !== false;
                $base_url = $is_local ? '/Etracker' : '';
                
                foreach ($image_ids as $idx => $image_id) {
                    if (!empty($image_id)) {
                        $images[] = [
                            'image_id' => $image_id,
                            'image_desc' => isset($image_descs[$idx]) ? $image_descs[$idx] : 'Program image',
                            'image_url' => $base_url . '/portal/home/backend/get_archived_image.php?image_id=' . $image_id
                        ];
                    }
                }
            }
            
            // If no archived images, use fallback from original program
            if (empty($images) && !empty($row['fallback_image_ids'])) {
                $image_ids = explode('||', $row['fallback_image_ids']);
                $image_descs = explode('||', $row['fallback_image_descs']);
                
                // Use same base URL detection
                $is_local = isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/Etracker/') !== false;
                $base_url = $is_local ? '/Etracker' : '';
                
                foreach ($image_ids as $idx => $image_id) {
                    if (!empty($image_id)) {
                        $images[] = [
                            'image_id' => $image_id,
                            'image_desc' => isset($image_descs[$idx]) ? $image_descs[$idx] : 'Program image',
                            'image_url' => $base_url . '/portal/home/backend/get_image.php?image_id=' . $image_id
                        ];
                    }
                }
            }
            
            // Remove the concatenated fields from output
            unset($row['archive_image_ids']);
            unset($row['archive_image_descs']);
            unset($row['fallback_image_ids']);
            unset($row['fallback_image_descs']);
            
            // Add images to program data
            $row['images'] = $images;
            $programs[] = $row;
        }
        
        // Cache the response
        $json_response = json_encode($programs);
        $cache->set($cache_key, $json_response, 300); // Cache for 5 minutes
        
        header('X-Cache: MISS');
        echo $json_response;
    } else {
        $json_response = json_encode([]);
        $cache->set($cache_key, $json_response, 300);
        
        header('X-Cache: MISS');
        echo $json_response;
    }

    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['error' => 'PHP Error: ' . $e->getMessage()]);
}
?>