<?php
require_once 'db.php';
echo "Testing database connection...\n";
if ($conn->connect_error) {
    echo 'Connection failed: ' . $conn->connect_error . "\n";
} else {
    echo "Database connected successfully!\n";

    // Test a simple query
    $result = $conn->query('SELECT COUNT(*) as count FROM programs');
    if ($result) {
        $row = $result->fetch_assoc();
        echo 'Current programs count: ' . $row['count'] . "\n";
    } else {
        echo 'Query failed: ' . $conn->error . "\n";
    }
}
?>