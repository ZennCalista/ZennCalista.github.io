<?php
// Backup copy of debug_upload.php moved to .trash on 2025-10-26
// Original content preserved for safety.

// Debug version of upload script
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

function debugResponse($message, $data = null) {
    echo json_encode([
        'debug' => true,
        'message' => $message,
        'data' => $data,
        'post' => $_POST,
        'files' => isset($_FILES) ? $_FILES : 'No files',
        'method' => $_SERVER['REQUEST_METHOD']
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debugResponse('Not a POST request');
}

try {
    include '../db.php';
    
    if ($conn->connect_error) {
        debugResponse('Database connection failed: ' . $conn->connect_error);
    }
    
    // Check if required fields exist
    $required_fields = ['program_name', 'department', 'location', 'status', 'description'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        debugResponse('Missing required fields', $missing_fields);
    }
    
    // Try a simple insert without images first
    $program_name = trim($_POST['program_name']);
    $department = trim($_POST['department']);
    $location = trim($_POST['location']);
    $status = trim($_POST['status']);
    $description = trim($_POST['description']);
    
    $sql = "INSERT INTO programs (program_name, department, location, status, description) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        debugResponse('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param("sssss", $program_name, $department, $location, $status, $description);
    
    if ($stmt->execute()) {
        debugResponse('Success! Program inserted with ID: ' . $conn->insert_id);
    } else {
        debugResponse('Failed to execute: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    debugResponse('Exception: ' . $e->getMessage());
}
?>
