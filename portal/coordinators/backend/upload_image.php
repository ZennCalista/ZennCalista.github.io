<?php
session_start();
header('Content-Type: application/json');

// Check if user is authenticated and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['image']) || !isset($_POST['coordinator_id'])) {
    echo json_encode(['success' => false, 'message' => 'No image or coordinator ID provided']);
    exit;
}

$coordinatorId = intval($_POST['coordinator_id']);
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

// Database configuration
$host = 'database-1.ch0e2sa0mf0l.ap-southeast-2.rds.amazonaws.com';
$user = 'admin';
$pass = 'admin1234!';
$dbname = 'etracker';

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    $conn->set_charset("utf8mb4");
    
    // Delete any existing image for this coordinator
    $deleteStmt = $conn->prepare("DELETE FROM coordinator_images WHERE coordinator_id = ?");
    $deleteStmt->bind_param("i", $coordinatorId);
    $deleteStmt->execute();
    $deleteStmt->close();
    
    // Insert new image
    $stmt = $conn->prepare("INSERT INTO coordinator_images (coordinator_id, image_data, image_type, file_size) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $coordinatorId, $imageData, $fileType, $fileSize);
    $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    echo json_encode(['success' => true, 'message' => 'Image uploaded successfully']);
    
} catch (Exception $e) {
    error_log('Upload image error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
}
