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
    $required_fields = ['program_name', 'department', 'location', 'description'];
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
    
    // Handle project titles (can be individual fields or single field)
    $project_titles = null;
    if (!empty($_POST['project_title_1']) || !empty($_POST['project_title_2']) || !empty($_POST['project_title_3'])) {
        $titles = [];
        if (!empty($_POST['project_title_1'])) $titles[] = trim($_POST['project_title_1']);
        if (!empty($_POST['project_title_2'])) $titles[] = trim($_POST['project_title_2']);
        if (!empty($_POST['project_title_3'])) $titles[] = trim($_POST['project_title_3']);
        $project_titles = json_encode($titles);
    } elseif (!empty($_POST['project_titles'])) {
        $project_titles = trim($_POST['project_titles']);
    }
    
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
    $program_type = !empty($_POST['program_type']) ? trim($_POST['program_type']) : null;
    $target_audience = !empty($_POST['target_audience']) ? trim($_POST['target_audience']) : null;
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $previous_date = !empty($_POST['previous_date']) ? $_POST['previous_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $status = !empty($_POST['status']) ? trim($_POST['status']) : 'planning'; // Default to planning if not set
    $max_students = !empty($_POST['max_students']) ? (int)$_POST['max_students'] : null;
    $male_count = !empty($_POST['male_count']) ? (int)$_POST['male_count'] : 0;
    $female_count = !empty($_POST['female_count']) ? (int)$_POST['female_count'] : 0;
    $description = trim($_POST['description']);
    $requirements = !empty($_POST['requirements']) ? trim($_POST['requirements']) : null;
    $budget = !empty($_POST['budget']) ? (float)$_POST['budget'] : null;
    
    // Handle SDG goals
    $sdg_goals = null;
    if (!empty($_POST['selected_sdgs'])) {
        $sdg_goals = $_POST['selected_sdgs'];
    } elseif (!empty($_POST['sdg_goals'])) {
        $sdg_goals = trim($_POST['sdg_goals']);
    }
    
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
    error_log("program_type: " . ($program_type ?? 'NULL'));
    error_log("target_audience: " . ($target_audience ?? 'NULL'));
    error_log("start_date: " . ($start_date ?? 'NULL'));
    error_log("previous_date: " . ($previous_date ?? 'NULL'));
    error_log("end_date: " . ($end_date ?? 'NULL'));
    error_log("status: " . $status);
    error_log("max_students: " . ($max_students ?? 'NULL'));
    error_log("male_count: " . $male_count);
    error_log("female_count: " . $female_count);
    error_log("description: " . $description);
    error_log("requirements: " . ($requirements ?? 'NULL'));
    error_log("budget: " . ($budget ?? 'NULL'));
    error_log("sdg_goals: " . ($sdg_goals ?? 'NULL'));
    error_log("faculty_id: " . ($faculty_id ?? 'NULL'));

    // Validate status enum
    $valid_statuses = ['planning', 'ongoing', 'ended', 'completed', 'cancelled'];
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

        $fields = ['program_name', 'department_id', 'location', 'description'];
        $values = [$program_name, $department_id, $location, $description];
        // types: s = program_name, i = department_id, s = location, s = description
        $types = 'siss';
        
        if ($program_type !== null) {
            $fields[] = 'program_type';
            $values[] = $program_type;
            $types .= 's';
        }
        
        if ($target_audience !== null) {
            $fields[] = 'target_audience';
            $values[] = $target_audience;
            $types .= 's';
        }
        
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
        
        if ($previous_date !== null) {
            $fields[] = 'previous_date';
            $values[] = $previous_date;
            $types .= 's';
        }
        
        if ($end_date !== null) {
            $fields[] = 'end_date';
            $values[] = $end_date;
            $types .= 's';
        }
        
        if ($status !== null) {
            $fields[] = 'status';
            $values[] = $status;
            $types .= 's';
        }
        
        if ($max_students !== null) {
            $fields[] = 'max_students';
            $values[] = $max_students;
            $types .= 'i';
        }
        
        if ($male_count > 0) {
            $fields[] = 'male_count';
            $values[] = $male_count;
            $types .= 'i';
        }
        
        if ($female_count > 0) {
            $fields[] = 'female_count';
            $values[] = $female_count;
            $types .= 'i';
        }
        
        if ($requirements !== null) {
            $fields[] = 'requirements';
            $values[] = $requirements;
            $types .= 's';
        }
        
        if ($budget !== null) {
            $fields[] = 'budget';
            $values[] = $budget;
            $types .= 'd';
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
        
        // Handle program sessions
        $created_sessions = [];
        if (isset($_POST['sessions']) && !empty($_POST['sessions'])) {
            $sessions = json_decode($_POST['sessions'], true);
            if (is_array($sessions)) {
                error_log("Processing " . count($sessions) . " sessions");
                
                // Create program_sessions table if it doesn't exist
                $create_sessions_table = "CREATE TABLE IF NOT EXISTS program_sessions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    program_id INT NOT NULL,
                    session_title VARCHAR(255) NOT NULL,
                    session_date DATE NOT NULL,
                    session_start TIME NOT NULL,
                    session_end TIME NOT NULL,
                    location VARCHAR(255) NULL,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
                )";
                
                if (!$conn->query($create_sessions_table)) {
                    throw new Exception('Failed to create program_sessions table: ' . $conn->error);
                }
                
                foreach ($sessions as $session) {
                    if (!empty($session['title']) && !empty($session['date']) && !empty($session['start']) && !empty($session['end'])) {
                        $session_sql = "INSERT INTO program_sessions (program_id, session_title, session_date, session_start, session_end, location) 
                                       VALUES (?, ?, ?, ?, ?, ?)";
                        $session_stmt = $conn->prepare($session_sql);
                        
                        if (!$session_stmt) {
                            throw new Exception('Failed to prepare session statement: ' . $conn->error);
                        }
                        
                        $session_location = !empty($session['location']) ? $session['location'] : null;
                        $session_stmt->bind_param("isssss", $program_id, $session['title'], $session['date'], $session['start'], $session['end'], $session_location);
                        
                        if (!$session_stmt->execute()) {
                            throw new Exception('Failed to insert session: ' . $session_stmt->error);
                        }
                        
                        $session_id = $conn->insert_id;
                        $created_sessions[] = [
                            'session_id' => $session_id,
                            'title' => $session['title'],
                            'date' => $session['date'],
                            'start' => $session['start'],
                            'end' => $session['end'],
                            'location' => $session_location
                        ];
                        
                        $session_stmt->close();
                        error_log("Session inserted successfully with ID: $session_id");
                    }
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        sendResponse(true, 'Program uploaded successfully', [
            'program_id' => $program_id,
            'uploaded_images' => $uploaded_images,
            'created_sessions' => $created_sessions
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
