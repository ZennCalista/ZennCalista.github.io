<?php
require_once '../backend/db.php';
$query = 'SELECT COUNT(*) as count FROM program_sessions';
$result = $conn->query($query);
$row = $result->fetch_assoc();
echo 'Sessions count: ' . $row['count'];
?>