<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, log them
ini_set('log_errors', 1);

// Prevent any output before headers
ob_start();

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Clear any previous output
ob_clean();

try {
    // Check if db.php exists - use __DIR__ for absolute path
    $db_path = __DIR__ . '/../db.php';
    if (!file_exists($db_path)) {
        throw new Exception('Database configuration file not found at: ' . $db_path);
    }
    
    include $db_path;
    
    // Include cache helper to invalidate cache after archiving
    require_once __DIR__ . '/cache_helper.php';
    $cache = new SimpleCache(__DIR__ . '/cache');
    
    // Check database connection
    if (!isset($conn) || $conn === null) {
        throw new Exception('Database connection is null');
    }
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    // Start transaction
    if (!$conn->begin_transaction()) {
        throw new Exception('Failed to start transaction');
    }
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        throw new Exception('Invalid program ID: ' . $id);
    }
    
    // Check if program is already archived (prevent duplicates)
    $check_stmt = $conn->prepare("SELECT id FROM programs WHERE id = ? AND is_archived = 1");
    if ($check_stmt) {
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $check_stmt->close();
            echo json_encode(['success' => true, 'message' => 'Program already archived']);
            exit;
        }
        $check_stmt->close();
    }
    
    // Check if there's already an archive entry for this program
    $archive_check = $conn->prepare("SELECT id FROM programs_archive WHERE original_program_id = ?");
    if ($archive_check) {
        $archive_check->bind_param("i", $id);
        $archive_check->execute();
        $archive_result = $archive_check->get_result();
        if ($archive_result->num_rows > 0) {
            $archive_check->close();
            echo json_encode(['success' => true, 'message' => 'Program already in archive']);
            exit;
        }
        $archive_check->close();
    }
    
    // Get the program data
    $stmt = $conn->prepare("SELECT * FROM programs WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $program = $result->fetch_assoc();
    
    if (!$program) {
        throw new Exception('Program not found with ID: ' . $id);
    }
    
    // Check if programs_archive table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'programs_archive'");
    if (!$table_check || $table_check->num_rows === 0) {
        throw new Exception('programs_archive table does not exist');
    }
    
    // Handle NULL values - convert NULL to empty string for string fields, but keep NULL for nullable foreign keys
    $sdg_goals = $program['sdg_goals'] ?? '';
    $faculty_id = $program['faculty_id']; // Keep NULL if it's NULL (don't convert to 0)
    $description = $program['description'] ?? '';
    $project_titles = $program['project_titles'] ?? '';

    
    if ($faculty_id === null) {
        // Insert with NULL for faculty_id; record original program id for restore
        $stmt = $conn->prepare("INSERT INTO programs_archive 
            (program_name, project_titles, department_id, department, location, start_date, end_date, status, max_students, description, sdg_goals, faculty_id, created_at, updated_at, original_program_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception('Prepare archive insert failed: ' . $conn->error);
        }
        
        // Type string without faculty_id (14 params + original_program_id)
        $stmt->bind_param("ssissssississi", 
            $program['program_name'],
            $project_titles,
            $program['department_id'],
            $program['department'],
            $program['location'],
            $program['start_date'],
            $program['end_date'],
            $program['status'],
            $program['max_students'],
            $description,
            $sdg_goals,
            $program['created_at'],
            $program['updated_at'],
            $id
        );
    } else {
        // Insert with faculty_id value
        $stmt = $conn->prepare("INSERT INTO programs_archive 
            (program_name, project_titles, department_id, department, location, start_date, end_date, status, max_students, description, sdg_goals, faculty_id, created_at, updated_at, original_program_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception('Prepare archive insert failed: ' . $conn->error);
        }
        
        // Type string with faculty_id (15 params including original_program_id)
        $stmt->bind_param("ssissssississsi", 
            $program['program_name'],
            $project_titles,
            $program['department_id'],
            $program['department'],
            $program['location'],
            $program['start_date'],
            $program['end_date'],
            $program['status'],
            $program['max_students'],
            $description,
            $sdg_goals,
            $faculty_id,
            $program['created_at'],
            $program['updated_at'],
            $id
        );
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to archive program: ' . $stmt->error);
    }
    
    // Get the new archive ID
    $archive_id = $conn->insert_id;
    
    // Copy program images if they exist
    $img_check = $conn->query("SHOW TABLES LIKE 'images'");
    if ($img_check && $img_check->num_rows > 0) {
        // Use prepared statement for security
        $img_stmt_select = $conn->prepare("SELECT image_name, image_desc FROM images WHERE program_id = ?");
        if ($img_stmt_select) {
            $img_stmt_select->bind_param("i", $id);
            $img_stmt_select->execute();
            $img_result = $img_stmt_select->get_result();
            
            if ($img_result && $img_result->num_rows > 0) {
                // Ensure images_archive table exists
                $create_images_archive = "CREATE TABLE IF NOT EXISTS images_archive (
                    archive_image_id INT AUTO_INCREMENT PRIMARY KEY,
                    archive_program_id INT NOT NULL,
                    image_data LONGBLOB,
                    image_desc VARCHAR(255),
                    uploaded_at DATETIME,
                    INDEX (archive_program_id)
                )";

                if (!$conn->query($create_images_archive)) {
                    throw new Exception('Failed to create images_archive table: ' . $conn->error);
                }
                
                while ($img = $img_result->fetch_assoc()) {
                    $img_stmt = $conn->prepare("INSERT INTO images_archive (archive_program_id, image_data, image_desc, uploaded_at) VALUES (?, ?, ?, NOW())");
                    if ($img_stmt) {
                        // image_name from images table becomes image_data in images_archive table
                        $img_stmt->bind_param("iss", $archive_id, $img['image_name'], $img['image_desc']);
                        $img_stmt->execute();
                        $img_stmt->close();
                    }
                }
            }
            $img_stmt_select->close();
        }
    }
    
    // Soft-archive the original program instead of deleting it to preserve FK integrity and images.
    $stmt = $conn->prepare("UPDATE programs SET is_archived = 1, archived_at = NOW(), updated_at = NOW() WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Prepare archive-update failed: ' . $conn->error);
    }

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        throw new Exception('Failed to mark original program as archived: ' . $stmt->error);
    }
    
    // Commit transaction
    if (!$conn->commit()) {
        throw new Exception('Failed to commit transaction: ' . $conn->error);
    }
    
    // Invalidate cache for both home page and archive page
    $cache->delete('programs_list_v3');
    $cache->delete('archived_programs_list_v3');
    
    $response = ['success' => true, 'message' => 'Program archived successfully'];
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback on error
    if (isset($conn) && $conn !== null) {
        try {
            $conn->rollback();
        } catch (Exception $rollbackError) {
            // Ignore rollback errors
        }
    }
    
    $response = ['success' => false, 'error' => $e->getMessage()];
    echo json_encode($response);
}

if (isset($conn) && $conn !== null) {
    $conn->close();
}

// Flush output
ob_end_flush();
?>