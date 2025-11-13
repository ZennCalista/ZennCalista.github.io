<?php
include 'backend/db.php';

$result = $conn->query("DESCRIBE programs");
echo "programs table:\n";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' ' . $row['Type'] . ' ' . ($row['Null'] == 'NO' ? 'NOT NULL' : 'NULL') . ' ' . ($row['Key'] ? $row['Key'] : '') . ' ' . ($row['Default'] ? 'DEFAULT ' . $row['Default'] : '') . ' ' . ($row['Extra'] ? $row['Extra'] : '') . "\n";
}

$result = $conn->query("SELECT COUNT(*) as count FROM programs");
$row = $result->fetch_assoc();
echo "\nPrograms count: " . $row['count'] . "\n";
?>