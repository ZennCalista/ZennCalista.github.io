<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Not logged in');
}

$user_id = $_SESSION['user_id'];
// Fetch faculty_id from faculty table using user_id
$faculty_id = null;
$stmt = $conn->prepare("SELECT id FROM faculty WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($faculty_id);
$stmt->fetch();
$stmt->close();

if (!$faculty_id) {
    die('Faculty ID not found for this user.');
}
$program_id = $_POST['program_id'];
$document_type = $_POST['document_type'];

if (!isset($_FILES['document_file'])) {
    die('No file uploaded');
}

// Handle multiple files
$numFiles = count($_FILES['document_file']['name']);

for ($i = 0; $i < $numFiles; $i++) {
    $error = $_FILES['document_file']['error'][$i];
    $name = $_FILES['document_file']['name'][$i];
    $tmp_name = $_FILES['document_file']['tmp_name'][$i];
    $size = $_FILES['document_file']['size'][$i];

    if ($error !== UPLOAD_ERR_OK) {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                die('File too large (server limit) for ' . $name);
            case UPLOAD_ERR_FORM_SIZE:
                die('File too large (form limit) for ' . $name);
            case UPLOAD_ERR_PARTIAL:
                die('File upload was partial for ' . $name);
            case UPLOAD_ERR_NO_FILE:
                die('No file was uploaded for ' . $name);
            case UPLOAD_ERR_NO_TMP_DIR:
                die('Missing temporary folder for ' . $name);
            case UPLOAD_ERR_CANT_WRITE:
                die('Failed to write file to disk for ' . $name);
            case UPLOAD_ERR_EXTENSION:
                die('File upload stopped by extension for ' . $name);
            default:
                die('Unknown upload error for ' . $name);
        }
    }

    // Validate file type and size
    $allowed_types = ['pdf', 'docx', 'jpg', 'jpeg', 'png'];
    $max_size = 10 * 1024 * 1024; // 10MB
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_types)) {
        die('Invalid file type for ' . $name);
    }
    if ($size > $max_size) {
        die('File too large for ' . $name);
    }

    // Prepare file info
    $original_filename = $name;
    $file_content = file_get_contents($tmp_name);

    // Move file
    $upload_dir = __DIR__ . '/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $unique_name = uniqid() . '_' . basename($name);
    $target_path = $upload_dir . $unique_name;
    if (!move_uploaded_file($tmp_name, $target_path)) {
        die('Failed to move file ' . $name);
    }
    $file_path = 'uploads/' . $unique_name; // Save relative path for DB

    // Insert into DB
    $sql = "INSERT INTO document_uploads (program_id, faculty_id, document_type, file_path, original_filename, file_blob, upload_date, status, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 'pending', ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iisssbi', $program_id, $faculty_id, $document_type, $file_path, $original_filename, $file_content, $user_id);
    $stmt->send_long_data(5, $file_content); // 5 is the index of file_blob (0-based)
    $stmt->execute();
    $stmt->close();
}

echo "Upload successful!";
?>