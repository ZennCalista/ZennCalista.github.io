<?php
require_once 'db.php';

// Check current ENUM definition
$result = $conn->query("SHOW COLUMNS FROM program_proposals LIKE 'status'");
if ($result && $row = $result->fetch_assoc()) {
    $type = $row['Type'];
    echo "Current type: $type\n";

    if (strpos($type, "'used'") === false) {
        // Update ENUM to include 'used' status
        $alter_sql = "ALTER TABLE program_proposals MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'used') DEFAULT 'pending'";
        echo "Running: $alter_sql\n";
        if ($conn->query($alter_sql)) {
            echo "✓ Successfully updated program_proposals status ENUM to include 'used'\n";
        } else {
            echo "✗ Error updating status ENUM: " . $conn->error . "\n";
        }
    } else {
        echo "✓ Status ENUM already includes 'used' status\n";
    }
} else {
    echo "✗ Could not check current status column definition\n";
}

$conn->close();
?>