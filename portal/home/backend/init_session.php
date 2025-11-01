<?php
header('Content-Type: application/json');
// Initialize a PHP session if one does not exist. Useful for pages that need a session cookie.
if (session_status() === PHP_SESSION_NONE) session_start();

// Optionally mark as initialized
$_SESSION['initialized_at'] = date('c');

echo json_encode([
    'session' => session_id(),
    'initialized' => true
]);
?>
