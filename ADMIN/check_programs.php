<?php
require_once '../backend/db.php';
$query = 'SELECT COUNT(*) as count FROM programs';
$result = $conn->query($query);
$row = $result->fetch_assoc();
echo 'Programs count: ' . $row['count'];
?>