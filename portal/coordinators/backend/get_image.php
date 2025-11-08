<?php
// Get coordinator ID
$coordinatorId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($coordinatorId <= 0) {
    header('HTTP/1.0 400 Bad Request');
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
    
    // Get image
    $stmt = $conn->prepare("SELECT image_data, image_type FROM coordinator_images WHERE coordinator_id = ? LIMIT 1");
    $stmt->bind_param("i", $coordinatorId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Set appropriate headers
        header('Content-Type: ' . $row['image_type']);
        header('Content-Length: ' . strlen($row['image_data']));
        header('Cache-Control: public, max-age=86400'); // Cache for 1 day
        
        // Output image data
        echo $row['image_data'];
    } else {
        // No image found, return 404
        header('HTTP/1.0 404 Not Found');
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log('Get image error: ' . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
}
