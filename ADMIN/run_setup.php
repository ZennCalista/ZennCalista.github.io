<?php
// Run database setup
require_once 'db.php';

echo "<h2>Running Database Setup</h2>";

try {
    $sql = file_get_contents('setup_database.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            if ($conn->query($statement) === TRUE) {
                echo '✅ Executed: ' . substr($statement, 0, 50) . '...<br>';
            } else {
                echo '❌ Error: ' . $conn->error . '<br>';
            }
        }
    }

    echo '<br>Database setup completed.<br>';

} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
}

$conn->close();
?>