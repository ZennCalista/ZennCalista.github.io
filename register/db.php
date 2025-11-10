<?php
$host = 'database-1.ch0e2sa0mf0l.ap-southeast-2.rds.amazonaws.com';
$user = 'admin';
$pass = 'admin1234!';
$dbname = 'etracker';

// Set a timeout for DNS resolution
ini_set('default_socket_timeout', 10);

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        error_log("MySQL Connection failed: " . $conn->connect_error);
        // For register page that expects JSON
        if (strpos($_SERVER['PHP_SELF'], 'register.php') !== false || 
            (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-Type: application/json');
            echo json_encode(["status" => "error", "message" => "Database connection failed. Please try again later."]);
            exit;
        }
        die("Database connection failed. Please try again later.");
    }
    $conn->set_charset('utf8mb4');
    error_log("Database connected successfully to $host");
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    // For register page that expects JSON
    if (strpos($_SERVER['PHP_SELF'], 'register.php') !== false || 
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Database error occurred. Please contact support."]);
        exit;
    }
    die("Database error occurred. Please contact support.");
}
?>