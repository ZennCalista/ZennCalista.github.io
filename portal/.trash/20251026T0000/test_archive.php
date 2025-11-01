<?php
// Backup copy of test_archive.php moved to .trash on 2025-10-26
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting test...\n";
echo "Current directory: " . getcwd() . "\n";
echo "Script directory: " . __DIR__ . "\n";

// Test different paths
$paths = [
    '../db.php',
    __DIR__ . '/../db.php',
    dirname(__DIR__) . '/db.php'
];

echo "\nTesting paths:\n";
foreach ($paths as $path) {
    $realpath = realpath($path);
    echo "  Path: $path\n";
    echo "    Exists: " . (file_exists($path) ? 'YES' : 'NO') . "\n";
    echo "    Realpath: " . ($realpath ? $realpath : 'NULL') . "\n";
}

// Test 1: Can we include db.php?
echo "\nTest 1: Including db.php...\n";
$db_path = __DIR__ . '/../db.php';
if (file_exists($db_path)) {
    echo "db.php exists at $db_path\n";
    include $db_path;
    echo "db.php included\n";
    
    if (isset($conn)) {
        echo "Connection variable exists\n";
        if ($conn->connect_error) {
            echo "Connection error: " . $conn->connect_error . "\n";
        } else {
            echo "Connection successful!\n";
            
            // Test 2: Check programs_archive table
            echo "\nTest 2: Checking programs_archive table...\n";
            $result = $conn->query("SHOW TABLES LIKE 'programs_archive'");
            if ($result && $result->num_rows > 0) {
                echo "programs_archive table exists\n";
                
                // Show columns
                $columns = $conn->query("DESCRIBE programs_archive");
                echo "Columns in programs_archive:\n";
                while ($col = $columns->fetch_assoc()) {
                    echo "  - {$col['Field']} ({$col['Type']})\n";
                }
            } else {
                echo "programs_archive table does NOT exist!\n";
            }
            
            // Test 3: Check a sample program
            echo "\nTest 3: Checking programs table...\n";
            $result = $conn->query("SELECT id, program_name FROM programs LIMIT 1");
            if ($result && $result->num_rows > 0) {
                $program = $result->fetch_assoc();
                echo "Sample program found: ID {$program['id']}, Name: {$program['program_name']}\n";
            } else {
                echo "No programs found in database\n";
            }
        }
    } else {
        echo "Connection variable not set!\n";
    }
} else {
    echo "db.php does NOT exist at $db_path\n";
}

echo "\nTest complete.\n";
?>
