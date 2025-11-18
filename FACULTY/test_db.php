<?php
require_once 'db.php';
echo "Database connected successfully!\n";

$result = $conn->query('DESCRIBE programs');
if ($result) {
    echo "Programs table columns:\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} else {
    echo 'Error: ' . $conn->error;
}
?>