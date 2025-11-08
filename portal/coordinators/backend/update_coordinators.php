<?php
session_start();
header('Content-Type: application/json');

// Check if user is authenticated and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get the JSON data from the request
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['coordinators']) || !is_array($data['coordinators'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data format']);
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
    $conn->begin_transaction();
    
    // Update each coordinator
    $stmt = $conn->prepare("UPDATE coordinators SET name = ?, department = ?, display_order = ? WHERE id = ?");
    
    foreach ($data['coordinators'] as $index => $coordinator) {
        if (isset($coordinator['id']) && isset($coordinator['name']) && isset($coordinator['dept'])) {
            $name = htmlspecialchars(trim($coordinator['name']), ENT_QUOTES, 'UTF-8');
            $dept = htmlspecialchars(trim($coordinator['dept']), ENT_QUOTES, 'UTF-8');
            $order = $index + 1; // Update display order based on array position
            $id = intval($coordinator['id']);
            
            $stmt->bind_param("ssii", $name, $dept, $order, $id);
            $stmt->execute();
        }
    }
    
    $stmt->close();
    $conn->commit();
    $conn->close();
    
    echo json_encode(['success' => true, 'message' => 'Coordinators updated successfully']);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
        $conn->close();
    }
    error_log('Update coordinators error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update coordinators']);
}
