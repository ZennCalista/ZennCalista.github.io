<?php
header('Content-Type: application/json');
include 'db.php';

$id = intval($_POST['session_id'] ?? 0);

$ok = $conn->query("DELETE FROM program_sessions WHERE id=$id");

if (!$ok) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}

echo json_encode(['success' => true]);
?>