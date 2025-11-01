<?php
session_start();
header('Content-Type: application/json');

$out = ['session' => $_SESSION];

// Include a small helpful hint when no session exists
if (empty($_SESSION)) {
    http_response_code(204);
    echo json_encode(['session' => new stdClass(), 'message' => 'No active session found. Log in first.']);
    exit;
}

echo json_encode($out);

?>
