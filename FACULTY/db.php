<?php
// Primary AWS RDS connection
$host_primary = 'database-1.ch0e2sa0mf0l.ap-southeast-2.rds.amazonaws.com';
$user_primary = 'admin';
$pass_primary = 'admin1234!';
$dbname_primary = 'etracker';

// Fallback local XAMPP connection
$host_fallback = 'localhost';
$user_fallback = 'root';
$pass_fallback = '';
$dbname_fallback = 'etracker';

// Attempt primary connection with error handling and timeout
$conn = null;
$primary_failed = false;

// Set connection timeout options
$timeout = 3; // 3 seconds timeout
$options = [
    MYSQLI_OPT_CONNECT_TIMEOUT => $timeout,
    MYSQLI_OPT_READ_TIMEOUT => $timeout
];

try {
    $conn = mysqli_init();
    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, $timeout);
    mysqli_options($conn, MYSQLI_OPT_READ_TIMEOUT, $timeout);

    if (!mysqli_real_connect($conn, $host_primary, $user_primary, $pass_primary, $dbname_primary)) {
        $primary_failed = true;
        error_log("Primary database connection failed: " . mysqli_connect_error());
    }
} catch (Exception $e) {
    $primary_failed = true;
    error_log("Primary database connection exception: " . $e->getMessage());
    $conn = null;
}

// If primary failed, try fallback
if ($primary_failed) {
    error_log("Attempting fallback to local XAMPP database.");

    try {
        $conn = new mysqli($host_fallback, $user_fallback, $pass_fallback, $dbname_fallback);
        if ($conn->connect_error) {
            error_log("Fallback database connection also failed: " . $conn->connect_error);
            die("Database connection failed: " . $conn->connect_error);
        } else {
            error_log("Successfully connected to fallback local database.");
        }
    } catch (Exception $e) {
        error_log("Fallback database connection exception: " . $e->getMessage());
        die("Database connection failed: " . $e->getMessage());
    }
}

// Set charset to UTF-8
$conn->set_charset("utf8");
?>
