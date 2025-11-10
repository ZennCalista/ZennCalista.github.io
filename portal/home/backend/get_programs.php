<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    include 'no_cache.php';
    include '../db.php';

    // Check database connection
    if ($conn->connect_error) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
        exit();
    }

    // OPTIMIZED: Query to get programs with department name AND all images in ONE query
    // This eliminates the N+1 query problem by using GROUP_CONCAT
    // Exclude programs that have been soft-archived (is_archived = 1)
    $sql = "SELECT 
            p.id, p.program_name, p.project_titles, d.department_name as department, p.location, 
            p.start_date, p.end_date, p.status, p.max_students, p.description, p.sdg_goals,
            GROUP_CONCAT(i.image_id ORDER BY i.image_id ASC SEPARATOR '||') as image_ids,
            GROUP_CONCAT(COALESCE(i.image_desc, 'Program image') ORDER BY i.image_id ASC SEPARATOR '||') as image_descs
        FROM programs p
        LEFT JOIN departments d ON p.department_id = d.department_id
        LEFT JOIN images i ON p.id = i.program_id
        WHERE (p.is_archived = 0 OR p.is_archived IS NULL)
        GROUP BY p.id, p.program_name, p.project_titles, d.department_name, p.location, 
                 p.start_date, p.end_date, p.status, p.max_students, p.description, p.sdg_goals
        ORDER BY p.id DESC";

    $result = $conn->query($sql);

    // Check for SQL errors
    if (!$result) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'SQL Error: ' . $conn->error]);
        exit();
    }

    // Check if records exist
    if ($result->num_rows > 0) {
        $programs = [];
        
        while($row = $result->fetch_assoc()) {
            $images = [];
            
            // Parse the concatenated image data
            if (!empty($row['image_ids'])) {
                $image_ids = explode('||', $row['image_ids']);
                $image_descs = explode('||', $row['image_descs']);
                
                foreach ($image_ids as $idx => $image_id) {
                    if (!empty($image_id)) {
                        // Detect environment: Local vs Hosted
                        // Local SCRIPT_NAME: /Etracker/portal/home/backend/get_programs.php
                        // Hosted SCRIPT_NAME: /portal/home/backend/get_programs.php
                        $is_local = isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/Etracker/') !== false;
                        $base_url = $is_local ? '/Etracker' : '';
                        
                        $images[] = [
                            'image_id' => $image_id,
                            'image_desc' => isset($image_descs[$idx]) ? $image_descs[$idx] : 'Program image',
                            'image_url' => $base_url . '/portal/home/backend/get_image.php?image_id=' . $image_id
                        ];
                    }
                }
            }
            
            // Remove the concatenated fields from output
            unset($row['image_ids']);
            unset($row['image_descs']);
            
            // Add images to program data
            $row['images'] = $images;
            $programs[] = $row;
        }
        
        // Return JSON
        header('Content-Type: application/json');
        echo json_encode($programs);
    } else {
        header('Content-Type: application/json');
        echo json_encode([]);
    }

    $conn->close();
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'PHP Error: ' . $e->getMessage()]);
}
?>
