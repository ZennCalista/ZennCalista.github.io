<?php
header('Content-Type: application/json');
include 'db.php';

$result = $conn->query("SELECT * FROM programs WHERE is_archived = 0 ORDER BY created_at DESC");
$programs = [];
while ($row = $result->fetch_assoc()) {
    $programs[] = $row;
}
echo json_encode($programs);
?>