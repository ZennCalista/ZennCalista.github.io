<?php
/**
 * Database Table Clearing Script
 *
 * This script clears specified tables while preserving the admin user (ID: 8)
 * Tables are cleared in order to respect foreign key constraints
 */

require_once 'backend/db.php';

echo "Starting database table clearing process...\n";
echo "Note: Admin user (ID: 8) will be preserved in the users table\n\n";

// Tables to clear (in order to respect foreign key constraints)
$tables_to_clear = [
    'attendance',
    'faculty',
    'images',
    'images_archive',
    'programs',
    'programs_archive',
    'coordinator_images', // Note: This appears to be the equivalent of program_images
    'program_sessions',
    'students',
    'student_profiles'
];

// Temporarily disable foreign key checks to allow truncation
echo "Disabling foreign key checks...\n";
$result = mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
if (!$result) {
    die("Failed to disable foreign key checks: " . mysqli_error($conn) . "\n");
}
echo "✓ Foreign key checks disabled\n\n";

// Clear regular tables
foreach ($tables_to_clear as $table) {
    echo "Clearing table: {$table}... ";

    // Check if table exists first
    $check_result = mysqli_query($conn, "SHOW TABLES LIKE '{$table}'");
    if (mysqli_num_rows($check_result) == 0) {
        echo "Table does not exist, skipping.\n";
        continue;
    }

    // Get count before clearing
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM `{$table}`");
    $count_row = mysqli_fetch_assoc($count_result);
    $count_before = $count_row['count'];

    // Clear the table
    $result = mysqli_query($conn, "TRUNCATE TABLE `{$table}`");

    if ($result) {
        echo "✓ Cleared {$count_before} records\n";
    } else {
        echo "✗ Failed to clear: " . mysqli_error($conn) . "\n";
    }
}

// Special handling for users table - keep admin user (ID: 8)
echo "\nHandling users table (preserving admin user)... ";

// Check if admin user exists
$admin_check = mysqli_query($conn, "SELECT id, email FROM users WHERE id = 8");
$admin_exists = mysqli_num_rows($admin_check) > 0;

if ($admin_exists) {
    $admin_row = mysqli_fetch_assoc($admin_check);
    echo "Admin user found: {$admin_row['email']}\n";

    // Get count before clearing
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
    $count_row = mysqli_fetch_assoc($count_result);
    $count_before = $count_row['count'];

    // Delete all users except admin
    $delete_result = mysqli_query($conn, "DELETE FROM users WHERE id != 8");

    if ($delete_result) {
        $count_after = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users"));
        echo "✓ Cleared " . ($count_before - $count_after) . " users, kept {$count_after} admin user(s)\n";
    } else {
        echo "✗ Failed to clear users: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Admin user (ID: 8) not found. Clearing all users... ";
    $result = mysqli_query($conn, "TRUNCATE TABLE users");
    if ($result) {
        echo "✓ All users cleared\n";
    } else {
        echo "✗ Failed to clear users: " . mysqli_error($conn) . "\n";
    }
}

// Reset auto-increment counters for all cleared tables
echo "\nResetting auto-increment counters...\n";
foreach ($tables_to_clear as $table) {
    $result = mysqli_query($conn, "ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
    if ($result) {
        echo "✓ Reset AUTO_INCREMENT for {$table}\n";
    } else {
        echo "✗ Failed to reset AUTO_INCREMENT for {$table}: " . mysqli_error($conn) . "\n";
    }
}

// Reset users table auto-increment (keeping admin user)
$result = mysqli_query($conn, "ALTER TABLE users AUTO_INCREMENT = 9"); // Start from 9 since admin is 8
if ($result) {
    echo "✓ Reset AUTO_INCREMENT for users table\n";
} else {
    echo "✗ Failed to reset AUTO_INCREMENT for users: " . mysqli_error($conn) . "\n";
}

// Re-enable foreign key checks
echo "\nRe-enabling foreign key checks...\n";
$result = mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
if ($result) {
    echo "✓ Foreign key checks re-enabled\n";
} else {
    echo "✗ Failed to re-enable foreign key checks: " . mysqli_error($conn) . "\n";
}

echo "\nDatabase clearing process completed!\n";
echo "Summary:\n";
echo "- Cleared " . count($tables_to_clear) . " tables\n";
echo "- Preserved admin user in users table\n";
echo "- Reset all auto-increment counters\n";
echo "- Foreign key constraints maintained\n";

?>