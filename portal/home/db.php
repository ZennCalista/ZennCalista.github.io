<?php
// Database connection settings
$db_server = 'localhost';
$db_user = 'root';
$db_pass = '';
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