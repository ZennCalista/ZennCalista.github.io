<?php
/**
 * Database Diagnostic Script
 * Checks database connection and table status
 */

ob_start();
ini_set('display_errors', '0');

require_once 'db.php';

ob_clean();
header('Content-Type: application/json');

try {
    $info = [];
    
    // Check connection
    $info['connection'] = [
        'status' => $conn->ping() ? 'Connected' : 'Disconnected',
        'host' => $conn->host_info,
        'server_version' => $conn->server_info,
        'client_version' => $conn->client_info
    ];
    
    // Check current database
    $dbResult = $conn->query("SELECT DATABASE() as db_name");
    $dbRow = $dbResult->fetch_assoc();
    $info['current_database'] = $dbRow['db_name'];
    
    // List all tables
    $tablesResult = $conn->query("SHOW TABLES");
    $tables = [];
    while ($row = $tablesResult->fetch_array()) {
        $tables[] = $row[0];
    }
    $info['all_tables'] = $tables;
    $info['total_tables'] = count($tables);
    
    // Check specifically for user_tokens table (case variations)
    $checkVariations = ['user_tokens', 'User_Tokens', 'USER_TOKENS', 'UserTokens'];
    $foundTokensTable = null;
    
    foreach ($checkVariations as $variation) {
        $check = $conn->query("SHOW TABLES LIKE '$variation'");
        if ($check && $check->num_rows > 0) {
            $foundTokensTable = $variation;
            break;
        }
    }
    
    $info['user_tokens_table'] = [
        'exists' => ($foundTokensTable !== null),
        'actual_name' => $foundTokensTable,
        'searched_variations' => $checkVariations
    ];
    
    // If table exists, get structure
    if ($foundTokensTable) {
        $structureResult = $conn->query("DESCRIBE $foundTokensTable");
        $structure = [];
        while ($row = $structureResult->fetch_assoc()) {
            $structure[] = $row;
        }
        $info['table_structure'] = $structure;
        
        // Get row count
        $countResult = $conn->query("SELECT COUNT(*) as total FROM $foundTokensTable");
        $countRow = $countResult->fetch_assoc();
        $info['row_count'] = $countRow['total'];
    }
    
    // Check users table
    $usersCheck = $conn->query("SHOW TABLES LIKE 'users'");
    $info['users_table_exists'] = ($usersCheck && $usersCheck->num_rows > 0);
    
    echo json_encode([
        'success' => true,
        'data' => $info
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

ob_end_flush();
