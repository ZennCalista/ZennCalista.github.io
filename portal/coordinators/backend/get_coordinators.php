<?php
header('Content-Type: application/json');

// Database configuration
$host = 'database-1.ch0e2sa0mf0l.ap-southeast-2.rds.amazonaws.com';
$user = 'admin';
$pass = 'admin1234!';
$dbname = 'etracker';

try {
    // Create database connection
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    $conn->set_charset("utf8mb4");
    
    // Get coordinators ordered by display_order
    $query = "SELECT c.id, c.name, c.department, c.email, c.phone, c.office_location, c.display_order,
              (SELECT COUNT(*) FROM coordinator_images WHERE coordinator_id = c.id) as has_image
              FROM coordinators c
              ORDER BY c.display_order ASC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }
    
    $coordinators = [];
    while ($row = $result->fetch_assoc()) {
        $coordinators[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'dept' => $row['department'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'office' => $row['office_location'],
            'order' => $row['display_order'],
            'hasImage' => $row['has_image'] > 0
        ];
    }
    
    $conn->close();
    
    echo json_encode(['success' => true, 'coordinators' => $coordinators]);
    
} catch (Exception $e) {
    error_log('Get coordinators error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
