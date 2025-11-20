<?php
// Alter the role ENUM to include non_acad
require_once 'db.php';

echo "<h2>Updating Role ENUM</h2>";

try {
    $alterQuery = "ALTER TABLE users MODIFY COLUMN role ENUM('admin','faculty','student','non_acad') NULL";

    if ($conn->query($alterQuery) === TRUE) {
        echo '✅ Successfully updated users table role ENUM to include non_acad<br>';
    } else {
        echo '❌ Error updating role ENUM: ' . $conn->error . '<br>';
    }

} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
}

$conn->close();
?>