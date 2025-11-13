<?php
include 'backend/db.php';

$result = $conn->query('SELECT COUNT(*) as count FROM programs');
$row = $result->fetch_assoc();
echo 'Total programs: ' . $row['count'] . PHP_EOL;

$result = $conn->query('SELECT id, program_name FROM programs');
while ($row = $result->fetch_assoc()) {
    echo $row['id'] . ': ' . $row['program_name'] . PHP_EOL;
}
?>