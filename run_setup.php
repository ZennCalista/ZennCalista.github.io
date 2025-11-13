<?php
include 'backend/db.php';

$sql = file_get_contents('ADMIN/setup_database.sql');

// Remove comments
$sql = preg_replace('/--.*$/m', '', $sql);

// Split by semicolon
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (!empty($statement)) {
        try {
            $conn->query($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}

echo "Setup complete!\n";
?>