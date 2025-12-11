<?php
require_once '../FACULTY/db.php';

echo "Running master tables migration...\n";

try {
    // Read and execute the SQL file
    $sql = file_get_contents('create_master_tables.sql');

    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            if ($conn->query($statement) === TRUE) {
                echo "✓ Success\n";
            } else {
                echo "✗ Error: " . $conn->error . "\n";
            }
        }
    }

    echo "\nMigration completed!\n";
    echo "Created tables:\n";
    echo "- master_students\n";
    echo "- master_faculty\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}

$conn->close();
?>