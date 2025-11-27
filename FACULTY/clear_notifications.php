<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Not logged in');
}

$user_id = $_SESSION['user_id'];

// Clear all active notifications by setting is_active to 0
$clear_sql = "UPDATE notifications SET is_active = 0 WHERE is_active = 1";
if ($conn->query($clear_sql)) {
    echo "Notifications cleared successfully";
} else {
    echo "Failed to clear notifications";
}
?>