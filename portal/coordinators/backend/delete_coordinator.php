<?php
session_start();
header('Content-Type: application/json');

// Check if user is authenticated and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get the ID from the request
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid coordinator ID']);
    exit;
}

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
    
    // Delete coordinator (images will be cascade deleted)
    $stmt = $conn->prepare("DELETE FROM coordinators WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $stmt->close();
        $conn->close();
        echo json_encode(['success' => true, 'message' => 'Coordinator deleted successfully']);
    } else {
        $stmt->close();
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Coordinator not found']);
    }
    
} catch (Exception $e) {
    error_log('Delete coordinator error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete coordinator']);
}
