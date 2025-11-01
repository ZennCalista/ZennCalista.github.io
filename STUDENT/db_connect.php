<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'etracker';

// Set a timeout for DNS resolution
ini_set('default_socket_timeout', 10);

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        error_log("MySQL Connection failed: " . $conn->connect_error);
        die("Database connection failed. Please try again later.");
    }
    $conn->set_charset('utf8mb4');
    error_log("Database connected successfully to $host");
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    die("Database error occurred. Please contact support.");
}
?>