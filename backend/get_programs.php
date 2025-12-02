<?php
// Start output buffering to catch any stray output
ob_start();

// Suppress any error output that might break JSON
error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json');

try {
    // Include db connection
    $host = 'database-1.ch0e2sa0mf0l.ap-southeast-2.rds.amazonaws.com';
    $user = 'admin';
    $pass = 'admin1234!';
    $dbname = 'etracker';
    
    ini_set('default_socket_timeout', 10);
    
    $conn = @new mysqli($host, $user, $pass, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    $conn->set_charset('utf8mb4');

    $includeArchived = isset($_GET['include_archived']) && ($_GET['include_archived'] === '1' || strtolower($_GET['include_archived']) === 'true');
    
    if ($includeArchived) {
        $result = $conn->query("SELECT * FROM programs ORDER BY created_at DESC");
    } else {
        $result = $conn->query("SELECT * FROM programs WHERE is_archived = 0 ORDER BY created_at DESC");
    }
    
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }
    
    $programs = [];
    while ($row = $result->fetch_assoc()) {
        $programs[] = $row;
    }
    
    // Clear any buffered output before sending JSON
    ob_end_clean();
    
    echo json_encode(['success' => true, 'data' => $programs]);
    exit;
    
} catch (Exception $e) {
    // Clear any buffered output before sending error
    ob_end_clean();
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
?>