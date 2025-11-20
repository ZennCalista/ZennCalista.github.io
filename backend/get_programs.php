<?php
header('Content-Type: application/json');
include 'db.php';

$includeArchived = isset($_GET['include_archived']) && ($_GET['include_archived'] === '1' || strtolower($_GET['include_archived']) === 'true');
if ($includeArchived) {
    $result = $conn->query("SELECT * FROM programs ORDER BY created_at DESC");
} else {
    $result = $conn->query("SELECT * FROM programs WHERE is_archived = 0 ORDER BY created_at DESC");
}
$programs = [];
while ($row = $result->fetch_assoc()) {
    $programs[] = $row;
}
echo json_encode($programs);
?>