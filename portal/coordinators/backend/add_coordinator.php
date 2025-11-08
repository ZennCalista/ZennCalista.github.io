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

if (!isset($data['name']) || !isset($data['department'])) {
    echo json_encode(['success' => false, 'message' => 'Name and department are required']);
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
    
    // Get the maximum display_order
    $result = $conn->query("SELECT MAX(display_order) as max_order FROM coordinators");
    $row = $result->fetch_assoc();
    $newOrder = ($row['max_order'] ?? 0) + 1;
    
    // Insert new coordinator
    $stmt = $conn->prepare("INSERT INTO coordinators (name, department, email, phone, office_location, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    
    $name = htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8');
    $dept = htmlspecialchars(trim($data['department']), ENT_QUOTES, 'UTF-8');
    $email = isset($data['email']) ? htmlspecialchars(trim($data['email']), ENT_QUOTES, 'UTF-8') : null;
    $phone = isset($data['phone']) ? htmlspecialchars(trim($data['phone']), ENT_QUOTES, 'UTF-8') : null;
    $office = isset($data['office']) ? htmlspecialchars(trim($data['office']), ENT_QUOTES, 'UTF-8') : null;
    
    $stmt->bind_param("sssssi", $name, $dept, $email, $phone, $office, $newOrder);
    $stmt->execute();
    
    $newId = $conn->insert_id;
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Coordinator added successfully',
        'id' => $newId,
        'order' => $newOrder
    ]);
    
} catch (Exception $e) {
    error_log('Add coordinator error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to add coordinator']);
}
