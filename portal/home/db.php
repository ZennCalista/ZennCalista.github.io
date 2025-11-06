<?php
// Database connection settings
$db_server = 'database-1.ch0e2sa0mf0l.ap-southeast-2.rds.amazonaws.com';
$db_user = 'admin';
$db_pass = 'admin1234!';
$db_name = 'etracker';

try {
    // PERFORMANCE OPTIMIZATION: Use persistent connection (p: prefix)
    // This reuses existing connections instead of creating new ones
    $conn = new mysqli('p:' . $db_server, 
                        $db_user, 
                        $db_pass, 
                        $db_name);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set character set for proper UTF-8 support and reduced overhead
    $conn->set_charset('utf8mb4');
    
    // Set connection timeout (5 seconds)
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    
    // Enable compression for data transfer (helps with remote databases)
    $conn->options(MYSQLI_CLIENT_COMPRESS, true);
    
} catch (Exception $e) {
    // Don't output anything here, let the calling script handle it
    $conn = null;
}
?>