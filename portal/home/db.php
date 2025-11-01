<?php
// Database connection settings
$db_server = 'database-1.ch0e2sa0mf0l.ap-southeast-2.rds.amazonaws.com';
$db_user = 'admin';
$db_pass = 'admin1234!';
$db_name = 'etracker';

try {
    $conn = new mysqli($db_server, 
                        $db_user, 
                        $db_pass, 
                        $db_name);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    // Don't output anything here, let the calling script handle it
    $conn = null;
}
?>