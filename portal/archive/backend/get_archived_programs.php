<?php
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

    // Query to get archived programs with department name from departments table
    // Include original_program_id so we can fallback to original program images when needed
    $sql = "SELECT pa.id, pa.original_program_id, pa.program_name, pa.project_titles, d.department_name as department, 
            pa.location, pa.start_date, pa.end_date, pa.status, pa.max_students, pa.description, pa.sdg_goals, 
            pa.created_at, pa.updated_at 
        FROM programs_archive pa
        LEFT JOIN departments d ON pa.department_id = d.department_id
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
        
        // Check if images_archive table exists (archived images are stored separately)
        $images_table_exists = false;
        $check_table = $conn->query("SHOW TABLES LIKE 'images_archive'");
        if ($check_table && $check_table->num_rows > 0) {
            $images_table_exists = true;
        }
        
        while($row = $result->fetch_assoc()) {
            $archive_id = (int)$row['id'];
            $original_id = isset($row['original_program_id']) ? (int)$row['original_program_id'] : 0;
            $images = [];

            // First try archived images table if available
            if ($images_table_exists) {
                try {
                    $image_sql = "SELECT archive_image_id, image_desc FROM images_archive WHERE archive_program_id = ? ORDER BY archive_image_id ASC";
                    $image_stmt = $conn->prepare($image_sql);

                    if ($image_stmt) {
                        $image_stmt->bind_param("i", $archive_id);
                        $image_stmt->execute();
                        $image_result = $image_stmt->get_result();

                        if ($image_result && $image_result->num_rows > 0) {
                            while($image_row = $image_result->fetch_assoc()) {
                                $images[] = [
                                    'image_id' => $image_row['archive_image_id'],
                                    'image_desc' => $image_row['image_desc'] ?: 'Program image',
                                    'image_url' => '../home/backend/get_archived_image.php?image_id=' . $image_row['archive_image_id']
                                ];
                            }
                        }
                        $image_stmt->close();
                    }
                } catch (Exception $e) {
                    error_log("Error fetching archived images for archive_id $archive_id: " . $e->getMessage());
                }
            }

            // If no archived images found, try falling back to images from the original program (if we have original_program_id)
            if (empty($images) && $original_id > 0) {
                try {
                    $image_sql2 = "SELECT image_id, image_desc FROM images WHERE program_id = ? ORDER BY image_id ASC";
                    $image_stmt2 = $conn->prepare($image_sql2);
                    if ($image_stmt2) {
                        $image_stmt2->bind_param('i', $original_id);
                        $image_stmt2->execute();
                        $image_result2 = $image_stmt2->get_result();
                        if ($image_result2) {
                            while ($image_row = $image_result2->fetch_assoc()) {
                                $images[] = [
                                    'image_id' => $image_row['image_id'],
                                    'image_desc' => $image_row['image_desc'] ?: 'Program image',
                                    'image_url' => '../home/backend/get_image.php?image_id=' . $image_row['image_id']
                                ];
                            }
                        }
                        $image_stmt2->close();
                    }
                } catch (Exception $e) {
                    error_log("Error fetching fallback images for original_id $original_id: " . $e->getMessage());
                }
            }
            
            // Add images to program data
            $row['images'] = $images;
            $programs[] = $row;
        }
        
        echo json_encode($programs);
    } else {
        echo json_encode([]);
    }

    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['error' => 'PHP Error: ' . $e->getMessage()]);
}
?>