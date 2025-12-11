<?php
require_once '../FACULTY/db.php';

echo "Checking master tables:\n";
$result = $conn->query('SHOW TABLES LIKE "master_%"');
while ($row = $result->fetch_array()) {
    echo "- " . $row[0] . "\n";
}
$result->free();

// Check master_students structure
echo "\nMaster Students table structure:\n";
$result = $conn->query('DESCRIBE master_students');
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
$result->free();

// Check master_faculty structure
echo "\nMaster Faculty table structure:\n";
$result = $conn->query('DESCRIBE master_faculty');
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
$result->free();

$conn->close();
?>