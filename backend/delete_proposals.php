<?php
require_once 'db.php';

try {
    $conn = getDBConnection();

    // Check what proposal-related tables exist
    $result = $conn->query('SHOW TABLES LIKE "%proposal%"');
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);

    echo "Found proposal tables: " . implode(', ', $tables) . "\n\n";

    // Show count of records in each table before deletion
    foreach ($tables as $table) {
        $count = $conn->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "$table: $count records\n";
    }

    echo "\n--- DELETING ALL PROPOSAL DATA ---\n";

    // Delete in correct order (respecting foreign keys)
    $deleteOrder = [
        'program_proposals', // Main table
        'document_uploads'   // Related documents
    ];

    foreach ($deleteOrder as $table) {
        if (in_array($table, $tables)) {
            $count = $conn->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            if ($count > 0) {
                $conn->exec("DELETE FROM `$table`");
                echo "✓ Deleted $count records from $table\n";
            } else {
                echo "✓ $table already empty\n";
            }
        }
    }

    // Reset auto-increment counters
    foreach ($tables as $table) {
        $conn->exec("ALTER TABLE `$table` AUTO_INCREMENT = 1");
        echo "✓ Reset auto-increment for $table\n";
    }

    echo "\n✅ All proposal data has been cleared successfully!\n";
    echo "You can now test the proposal system from scratch.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>