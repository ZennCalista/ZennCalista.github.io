<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Include database connection
require_once 'db.php';

echo "<h2>Token Migration Debug</h2>";
echo "<pre>";

// 1. Check connection
echo "\n=== DATABASE CONNECTION ===\n";
echo "Connection status: " . ($conn ? " Connected" : " Failed") . "\n";
if ($conn) {
    $dbName = mysqli_fetch_assoc(mysqli_query($conn, "SELECT DATABASE() as db"))['db'];
    echo "Current database: " . $dbName . "\n";
    echo "Server version: " . mysqli_get_server_info($conn) . "\n";
}

// 2. Check current tables
echo "\n=== CURRENT TABLES ===\n";
$tables = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_array($tables)) {
    echo "- " . $row[0] . "\n";
}

// 3. Read SQL file
echo "\n=== SQL FILE CONTENT ===\n";
$sqlFile = 'create_tokens_table.sql';
if (file_exists($sqlFile)) {
    $sqlContent = file_get_contents($sqlFile);
    echo "File size: " . strlen($sqlContent) . " bytes\n";
    echo "First 200 chars:\n" . substr($sqlContent, 0, 200) . "...\n\n";
    echo "Full SQL:\n" . $sqlContent . "\n";
} else {
    echo " File not found: $sqlFile\n";
}

// 4. Try to execute SQL
echo "\n=== EXECUTING SQL ===\n";
if (isset($sqlContent)) {
    // Split statements
    $statements = array_filter(
        array_map('trim', 
            preg_split('/;(?=(?:[^\'"]*[\'"][^\'"]*[\'"])*[^\'"]*$)/', $sqlContent)
        )
    );
    
    echo "Found " . count($statements) . " statements\n\n";
    
    $i = 1;
    foreach ($statements as $statement) {
        if (empty($statement) || strpos(trim($statement), '--') === 0) {
            continue;
        }
        
        echo "--- Statement $i ---\n";
        echo substr($statement, 0, 150) . (strlen($statement) > 150 ? '...' : '') . "\n";
        
        $result = mysqli_query($conn, $statement);
        if ($result) {
            echo " SUCCESS\n\n";
        } else {
            echo " ERROR: " . mysqli_error($conn) . "\n";
            echo "Error number: " . mysqli_errno($conn) . "\n\n";
        }
        $i++;
    }
}

// 5. Verify table exists
echo "\n=== VERIFICATION ===\n";
$check = mysqli_query($conn, "SHOW TABLES LIKE 'user_tokens'");
if ($check && mysqli_num_rows($check) > 0) {
    echo " user_tokens table EXISTS\n\n";
    
    // Show structure
    echo "Table structure:\n";
    $structure = mysqli_query($conn, "DESCRIBE user_tokens");
    while ($row = mysqli_fetch_assoc($structure)) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
} else {
    echo " user_tokens table DOES NOT EXIST\n";
    
    // Try case variations
    echo "\nChecking case variations:\n";
    $variations = ['user_tokens', 'USER_TOKENS', 'User_Tokens'];
    foreach ($variations as $var) {
        $c = mysqli_query($conn, "SHOW TABLES LIKE '$var'");
        echo "  - $var: " . ($c && mysqli_num_rows($c) > 0 ? "Found" : "Not found") . "\n";
    }
}

echo "</pre>";
?>
