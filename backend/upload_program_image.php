<?php
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['image']) || !isset($_POST['program_id'])) {
    echo json_encode(['success' => false, 'message' => 'No image or program ID provided']);
    exit;
}

$programId = intval($_POST['program_id']);
$file = $_FILES['image'];

// Validate file
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload error']);
    exit;
}

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$fileType = mime_content_type($file['tmp_name']);

if (!in_array($fileType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only images are allowed.']);
    exit;
}

// Validate file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 5MB.']);
    exit;
}

// Read file data
$imageData = file_get_contents($file['tmp_name']);
$fileSize = $file['size'];

try {
    // Delete any existing image for this program
    $deleteStmt = $conn->prepare("DELETE FROM program_images WHERE program_id = ?");
    $deleteStmt->bind_param("i", $programId);
    $deleteStmt->execute();
    $deleteStmt->close();

    // Insert new image
    $stmt = $conn->prepare("INSERT INTO program_images (program_id, image_data, image_type, file_size) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $programId, $imageData, $fileType, $fileSize);
    $stmt->execute();

    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Image uploaded successfully']);

} catch (Exception $e) {
    error_log('Upload program image error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
}

$conn->close();
?>