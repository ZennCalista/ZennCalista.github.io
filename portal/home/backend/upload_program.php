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
// Allow using logged-in user id (session) or fallback to posted faculty_id
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
    error_log("POST data received: " . print_r($_POST, true));
    error_log("FILES data received: " . print_r($_FILES, true));
    
    // Check database connection
    if ($conn->connect_error) {
        sendResponse(false, 'Database connection failed: ' . $conn->connect_error);
    }

    // Validate required fields
    $required_fields = ['program_name', 'department', 'location', 'status', 'description'];
    foreach ($required_fields as $field) {
        if ($field === 'department') {
            // department may be submitted as department or department_hidden (when the visible select is disabled)
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
    // department may be submitted as an ID (preferred) or a name (legacy). We'll resolve to department_id.
    // Accept department from the visible select ('department') or the hidden fallback ('department_hidden')
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
    // Prefer the authenticated user's id (renamed from faculty_id -> id). Fallback to POSTed faculty_id for compatibility.
    $faculty_id = null;
    if (!empty($_SESSION['user']['id'])) {
        $faculty_id = (int)$_SESSION['user']['id'];
    } elseif (!empty($_POST['faculty_id'])) {
        $faculty_id = (int)$_POST['faculty_id'];
    }
    
    // Debug the data being inserted
    // Try to resolve department_raw into department_id (if set)
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

    error_log("Data to insert:");
    error_log("program_name: " . $program_name);
    error_log("project_titles: " . ($project_titles ?? 'NULL'));
    error_log("department_raw: " . ($department_raw ?? 'NULL'));
    error_log("department_id: " . ($department_id ?? 'NULL'));
    error_log("location: " . $location);
    error_log("start_date: " . ($start_date ?? 'NULL'));
    error_log("end_date: " . ($end_date ?? 'NULL'));
    error_log("status: " . $status);
    error_log("max_students: " . $max_students);
    error_log("description: " . $description);
    error_log("sdg_goals: " . ($sdg_goals ?? 'NULL'));
    error_log("faculty_id: " . ($faculty_id ?? 'NULL'));

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
        // Build dynamic SQL based on which fields have values
        // department must resolve to department_id (integer) - if resolution failed, error out
        if ($department_raw !== '' && $department_id === null) {
            throw new Exception('Unknown department: ' . $department_raw);
        }

        $fields = ['program_name', 'department_id', 'location', 'status', 'description'];
        $values = [$program_name, $department_id, $location, $status, $description];
        // types: s = program_name, i = department_id, s = location, s = status, s = description
        $types = 'sisss';
        
        if ($project_titles !== null) {
            $fields[] = 'project_titles';
            $values[] = $project_titles;
            $types .= 's';
        }
        
        if ($start_date !== null) {
            $fields[] = 'start_date';
            $values[] = $start_date;
            $types .= 's';
        }
        
        if ($end_date !== null) {
            $fields[] = 'end_date';
            $values[] = $end_date;
            $types .= 's';
        }
        
        if ($max_students > 0) {
            $fields[] = 'max_students';
            $values[] = $max_students;
            $types .= 'i';
        }
        
        if ($sdg_goals !== null) {
            $fields[] = 'sdg_goals';
            $values[] = $sdg_goals;
            $types .= 's';
        }
        
        if ($faculty_id !== null) {
            // Determine which FK column exists in programs table (support migrations)
            $fkColumn = null;
            $checkCandidates = ['faculty_id','user_id','id'];
            foreach ($checkCandidates as $cand) {
                $res = $conn->query("SHOW COLUMNS FROM programs LIKE '" . $conn->real_escape_string($cand) . "'");
                if ($res && $res->num_rows > 0) { $fkColumn = $cand; break; }
            }
            // Default to 'faculty_id' if detection fails (backwards compat)
            if ($fkColumn === null) $fkColumn = 'faculty_id';

            $fields[] = $fkColumn;
            $values[] = $faculty_id;
            $types .= 'i';
        }
        
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        $program_sql = "INSERT INTO programs (" . implode(', ', $fields) . ") VALUES ($placeholders)";
        
        error_log("SQL: " . $program_sql);
        error_log("Types: " . $types);
        error_log("Values: " . print_r($values, true));
        
        $program_stmt = $conn->prepare($program_sql);
        if (!$program_stmt) {
            throw new Exception('Failed to prepare program statement: ' . $conn->error);
        }

        $program_stmt->bind_param($types, ...$values);

        if (!$program_stmt->execute()) {
            throw new Exception('Failed to insert program: ' . $program_stmt->error);
        }

        $program_id = $conn->insert_id;
        $program_stmt->close();

        // Handle image uploads
        $uploaded_images = [];
        error_log("Checking for images...");
        error_log("FILES images: " . print_r($_FILES['images'] ?? 'not set', true));
        
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            error_log("Images found, processing...");
            $image_descriptions = $_POST['image_descriptions'] ?? [];
            error_log("Image descriptions: " . print_r($image_descriptions, true));
            
            // Create images table if it doesn't exist
            $create_images_table = "CREATE TABLE IF NOT EXISTS images (
                image_id INT AUTO_INCREMENT PRIMARY KEY,
                program_id INT NOT NULL,
                image_name LONGBLOB,
                image_desc VARCHAR(255),
                FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
            )";
            
            if (!$conn->query($create_images_table)) {
                throw new Exception('Failed to create images table: ' . $conn->error);
            }

            // Process each uploaded image
            error_log("Processing " . count($_FILES['images']['name']) . " images");
            for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                error_log("Processing image $i: " . $_FILES['images']['name'][$i]);
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
                    
                    // Insert image into database
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
                    error_log("Image inserted successfully with ID: $image_id");
                    
                    $uploaded_images[] = [
                        'image_id' => $image_id,
                        'image_desc' => $image_desc,
                        'original_name' => $image_name
                    ];
                    
                    $image_stmt->close();
                } else {
                    // Handle upload errors
                    $error_messages = [
                        UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
                        UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
                        UPLOAD_ERR_PARTIAL => 'File upload incomplete',
                        UPLOAD_ERR_NO_FILE => 'No file uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'No temporary directory',
                        UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
                        UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
                    ];
                    
                    $error_code = $_FILES['images']['error'][$i];
                    $error_msg = isset($error_messages[$error_code]) ? $error_messages[$error_code] : 'Unknown upload error';
                    throw new Exception("Upload error for image $i: $error_msg");
                }
            }
        } else {
            error_log("No images to process or images array is empty");
        }

        error_log("Total images processed: " . count($uploaded_images));
        
        // Commit transaction
        $conn->commit();
        
        sendResponse(true, 'Program uploaded successfully', [
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
