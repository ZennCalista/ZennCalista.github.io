<?php
include 'db.php';

// Read and execute the SQL file
$sql = file_get_contents('create_otp_table.sql');

try {
    if ($conn->multi_query($sql)) {
        echo "OTP table setup completed successfully!\n";
        echo "- Created otp_verifications table\n";
        echo "- Added email_verified column to users table\n";
    } else {
        echo "Error setting up OTP table: " . $conn->error . "\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

$conn->close();
?>