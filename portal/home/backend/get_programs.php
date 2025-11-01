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

    // Query to get programs
    // Exclude programs that have been soft-archived (is_archived = 1)
    $sql = "SELECT id, program_name, project_titles, department, location, start_date, end_date, status, max_students, description, sdg_goals 
        FROM programs 
        WHERE (is_archived = 0 OR is_archived IS NULL)
        ORDER BY id DESC";

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
        
        // First, check if images table exists
        $images_table_exists = false;
        $check_table = $conn->query("SHOW TABLES LIKE 'images'");
        if ($check_table && $check_table->num_rows > 0) {
            $images_table_exists = true;
        }
        
        while($row = $result->fetch_assoc()) {
            $program_id = $row['id'];
            $images = [];
            
            // Only try to get images if the table exists
            if ($images_table_exists) {
                try {
                    // Get images for this program - use program_id foreign key
                    $image_sql = "SELECT image_id, image_desc FROM images WHERE program_id = ? ORDER BY image_id ASC";
                    $image_stmt = $conn->prepare($image_sql);
                    
                    if ($image_stmt) {
                        $image_stmt->bind_param("i", $program_id);
                        $image_stmt->execute();
                        $image_result = $image_stmt->get_result();
                        
                        if ($image_result) {
                            while($image_row = $image_result->fetch_assoc()) {
                                // Store image metadata and create URL to serve the image
                                $images[] = [
                                    'image_id' => $image_row['image_id'],
                                    'image_desc' => $image_row['image_desc'] ?: 'Program image',
                                    'image_url' => 'backend/get_image.php?image_id=' . $image_row['image_id']
                                ];
                            }
                        }
                        $image_stmt->close();
                    }
                } catch (Exception $e) {
                    // If there's an error with images, just continue without them
                    error_log("Error fetching images for program $program_id: " . $e->getMessage());
                }
            }
            
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
