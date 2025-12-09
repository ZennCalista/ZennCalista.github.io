<?php
$host = 'database-1.ch0e2sa0mf0l.ap-southeast-2.rds.amazonaws.com';
$user = 'admin';
$pass = 'admin1234!';
$dbname = 'etracker';

// Set a timeout for DNS resolution
ini_set('default_socket_timeout', 10);

$conn = null;
$db_error = null;

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        error_log("MySQL Connection failed: " . $conn->connect_error);
        $db_error = "Database connection failed. Please try again later.";
        $conn = null;
        // Only die if not called from an API endpoint (JSON content type)
        if (!headers_sent() && !isset($_SERVER['HTTP_ACCEPT']) || strpos($_SERVER['HTTP_ACCEPT'], 'application/json') === false) {
            die($db_error);
        }
    } else {
        $conn->set_charset('utf8mb4');
        // error_log("Database connected successfully to $host"); // Commented out to prevent output in API responses
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $db_error = "Database error occurred. Please contact support.";
    $conn = null;
    // Only die if not called from an API endpoint (JSON content type)
    if (!headers_sent() && (!isset($_SERVER['HTTP_ACCEPT']) || strpos($_SERVER['HTTP_ACCEPT'], 'application/json') === false)) {
        die($db_error);
    }
}
?>