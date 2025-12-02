<?php
session_start();
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Endpoint reachable',
    'session_exists' => isset($_SESSION['user_id']),
    'user_id' => $_SESSION['user_id'] ?? 'not set',
    'email' => $_SESSION['email'] ?? 'not set'
]);
?>
