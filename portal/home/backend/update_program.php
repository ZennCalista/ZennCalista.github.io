<?php
// Disable display_errors to prevent HTML output that breaks JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Include necessary files
include 'no_cache.php';
include '../db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Function to send JSON response
function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

try {
    // Debug: Log received data
    error_log("UPDATE - POST data received: " . print_r($_POST, true));
    error_log("UPDATE - FILES data received: " . print_r($_FILES, true));
    
    // Check database connection
    if ($conn->connect_error) {
        sendResponse(false, 'Database connection failed: ' . $conn->connect_error);
    }

    // Validate program ID
    if (empty($_POST['program_id'])) {
        sendResponse(false, "Program ID is required");
    }
    $program_id = (int)$_POST['program_id'];

    // Validate required fields
    $required_fields = ['program_name', 'department', 'location', 'status', 'description'];
    foreach ($required_fields as $field) {
        if ($field === 'department') {
            if (empty($_POST['department']) && empty($_POST['department_hidden'])) {
                sendResponse(false, "Required field 'department' is missing");
            }
        } else {
            if (empty($_POST[$field])) {
                sendResponse(false, "Required field '$field' is missing");
            }
        }
    }

    // Sanitize and prepare data
    $program_name = trim($_POST['program_name']);
    $project_titles = !empty($_POST['project_titles']) ? trim($_POST['project_titles']) : null;
    
    // Resolve department
    if (isset($_POST['department'])) {
        $department_raw = trim($_POST['department']);
    } elseif (isset($_POST['department_hidden'])) {
        $department_raw = trim($_POST['department_hidden']);
    } else {
        $department_raw = '';
    }
    $department_id = null;
    
    $location = trim($_POST['location']);
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $status = trim($_POST['status']);
    $max_students = !empty($_POST['max_students']) ? (int)$_POST['max_students'] : 0;
    $description = trim($_POST['description']);
    $sdg_goals = !empty($_POST['sdg_goals']) ? trim($_POST['sdg_goals']) : null;

    // Resolve department_raw into department_id
    if ($department_raw !== '') {
        if (ctype_digit($department_raw)) {
            $department_id = (int)$department_raw;
        } else {
            // try to lookup by name
            $stmtDept = $conn->prepare('SELECT department_id FROM departments WHERE TRIM(department_name) = ? LIMIT 1');
            if ($stmtDept) {
                $deptName = $department_raw;
                $stmtDept->bind_param('s', $deptName);
                $stmtDept->execute();
                $resDept = $stmtDept->get_result();
                if ($rowDept = $resDept->fetch_assoc()) {
                    $department_id = (int)$rowDept['department_id'];
                }
                $stmtDept->close();
            }
        }
    }

    if ($department_raw !== '' && $department_id === null) {
        throw new Exception('Unknown department: ' . $department_raw);
    }

    // Validate status enum
    $valid_statuses = ['planning', 'ongoing', 'ended', 'completed'];
    if (!in_array($status, $valid_statuses)) {
        sendResponse(false, 'Invalid status value');
    }

    // Validate dates
    if ($start_date && $end_date && strtotime($start_date) > strtotime($end_date)) {
        sendResponse(false, 'End date must be after start date');
    }

    // Begin transaction
    $conn->autocommit(false);

    try {
        // Build UPDATE SQL
        $update_fields = [
            'program_name = ?',
            'department_id = ?',
            'location = ?',
            'status = ?',
            'description = ?'
        ];
        $values = [$program_name, $department_id, $location, $status, $description];
        $types = 'sisss';
        
        if ($project_titles !== null) {
            $update_fields[] = 'project_titles = ?';
            $values[] = $project_titles;
            $types .= 's';
        }
        
        if ($start_date !== null) {
            $update_fields[] = 'start_date = ?';
            $values[] = $start_date;
            $types .= 's';
        }
        
        if ($end_date !== null) {
            $update_fields[] = 'end_date = ?';
            $values[] = $end_date;
            $types .= 's';
        }
        
        if ($max_students > 0) {
            $update_fields[] = 'max_students = ?';
            $values[] = $max_students;
            $types .= 'i';
        }
        
        if ($sdg_goals !== null) {
            $update_fields[] = 'sdg_goals = ?';
            $values[] = $sdg_goals;
            $types .= 's';
        }
        
        // Add program_id for WHERE clause
        $values[] = $program_id;
        $types .= 'i';
        
        $update_sql = "UPDATE programs SET " . implode(', ', $update_fields) . " WHERE id = ?";
        
        error_log("UPDATE SQL: " . $update_sql);
        error_log("Types: " . $types);
        error_log("Values: " . print_r($values, true));
        
        $update_stmt = $conn->prepare($update_sql);
        if (!$update_stmt) {
            throw new Exception('Failed to prepare update statement: ' . $conn->error);
        }

        $update_stmt->bind_param($types, ...$values);

        if (!$update_stmt->execute()) {
            throw new Exception('Failed to update program: ' . $update_stmt->error);
        }

        $update_stmt->close();

        // Handle image removal
        if (isset($_POST['images_to_remove'])) {
            $images_to_remove = json_decode($_POST['images_to_remove'], true);
            if (is_array($images_to_remove) && !empty($images_to_remove)) {
                $placeholders = implode(',', array_fill(0, count($images_to_remove), '?'));
                $delete_sql = "DELETE FROM images WHERE image_id IN ($placeholders) AND program_id = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                
                if (!$delete_stmt) {
                    throw new Exception('Failed to prepare delete image statement: ' . $conn->error);
                }
                
                // Build bind params
                $delete_values = $images_to_remove;
                $delete_values[] = $program_id;
                $delete_types = str_repeat('i', count($images_to_remove)) . 'i';
                
                $delete_stmt->bind_param($delete_types, ...$delete_values);
                
                if (!$delete_stmt->execute()) {
                    throw new Exception('Failed to delete images: ' . $delete_stmt->error);
                }
                
                $delete_stmt->close();
                error_log("Deleted " . count($images_to_remove) . " images");
            }
        }

        // Handle new image uploads
        $uploaded_images = [];
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            error_log("Processing new images for update...");
            $image_descriptions = $_POST['image_descriptions'] ?? [];
            
            for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $image_tmp = $_FILES['images']['tmp_name'][$i];
                    $image_name = $_FILES['images']['name'][$i];
                    $image_size = $_FILES['images']['size'][$i];
                    $image_type = $_FILES['images']['type'][$i];
                    
                    // Validate image type
                    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($image_type, $allowed_types)) {
                        throw new Exception("Invalid image type for file: $image_name");
                    }
                    
                    // Validate image size (5MB max)
                    if ($image_size > 5 * 1024 * 1024) {
                        throw new Exception("Image file too large: $image_name (max 5MB)");
                    }
                    
                    // Read image data
                    $image_data = file_get_contents($image_tmp);
                    if ($image_data === false) {
                        throw new Exception("Failed to read image file: $image_name");
                    }
                    
                    // Get image description
                    $image_desc = isset($image_descriptions[$i]) ? trim($image_descriptions[$i]) : '';
                    if (empty($image_desc)) {
                        $image_desc = "Image for " . $program_name;
                    }
                    
                    // Insert new image
                    $image_sql = "INSERT INTO images (program_id, image_name, image_desc) VALUES (?, ?, ?)";
                    $image_stmt = $conn->prepare($image_sql);
                    
                    if (!$image_stmt) {
                        throw new Exception('Failed to prepare image statement: ' . $conn->error);
                    }
                    
                    $image_stmt->bind_param("iss", $program_id, $image_data, $image_desc);
                    
                    if (!$image_stmt->execute()) {
                        throw new Exception('Failed to insert image: ' . $image_stmt->error);
                    }
                    
                    $image_id = $conn->insert_id;
                    error_log("New image inserted successfully with ID: $image_id");
                    
                    $uploaded_images[] = [
                        'image_id' => $image_id,
                        'image_desc' => $image_desc,
                        'original_name' => $image_name
                    ];
                    
                    $image_stmt->close();
                }
            }
        }

        // Commit transaction
        $conn->commit();
        
        sendResponse(true, 'Program updated successfully', [
            'program_id' => $program_id,
            'uploaded_images' => $uploaded_images
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    sendResponse(false, $e->getMessage());
} finally {
    // Close connection
    if (isset($conn)) {
        $conn->close();
    }
}
?>