<?php
// Migration script to add super_admin role to users table
require_once 'db.php';

echo "Starting migration to add super_admin role...\n";

try {
    // Alter the role ENUM to include super_admin
    $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin','faculty','student','non_acad','super_admin') NULL";
    
    if ($conn->query($sql) === TRUE) {
        echo " Successfully added super_admin to role ENUM\n";
    } else {
        throw new Exception("Error updating role ENUM: " . $conn->error);
    }
    
    // Verify the change
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($result && $row = $result->fetch_assoc()) {
        echo " Current role ENUM: " . $row['Type'] . "\n";
    }
    
    echo "\nMigration completed successfully!\n";
    echo "You can now create super_admin users.\n";
    
} catch (Exception $e) {
    echo " Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
?>
