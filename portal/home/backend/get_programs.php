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

    // Query to get programs with department name from departments table
    // Exclude programs that have been soft-archived (is_archived = 1)
    $sql = "SELECT p.id, p.program_name, p.project_titles, d.department_name as department, p.location, 
            p.start_date, p.end_date, p.status, p.max_students, p.description, p.sdg_goals 
        FROM programs p
        LEFT JOIN departments d ON p.department_id = d.department_id
        WHERE (p.is_archived = 0 OR p.is_archived IS NULL)
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
                                // Use absolute path from site root for cross-page compatibility
                                $images[] = [
                                    'image_id' => $image_row['image_id'],
                                    'image_desc' => $image_row['image_desc'] ?: 'Program image',
                                    'image_url' => '/Etracker/portal/home/backend/get_image.php?image_id=' . $image_row['image_id']
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
