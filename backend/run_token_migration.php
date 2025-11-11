<?php
/**
 * Token Migration Runner
 * Creates the user_tokens table for multi-device authentication
 */

// Start output buffering
ob_start();

// Disable error display
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once 'db.php';

// Clear buffer and set headers
ob_clean();
header('Content-Type: application/json');

try {
    // Verify database connection
    if (!isset($conn) || !$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Check current database
    $dbCheck = $conn->query("SELECT DATABASE() as db_name");
    $currentDb = $dbCheck->fetch_assoc();
    error_log("Running migration on database: " . $currentDb['db_name']);
    // Read the SQL file
    $sqlFile = __DIR__ . '/create_tokens_table.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception('Migration file not found');
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Remove comments and split by semicolon
    $statements = array_filter(
        array_map('trim', 
            preg_split('/;(?=(?:[^\'"]*[\'"][^\'"]*[\'"])*[^\'"]*$)/', $sql)
        )
    );
    
    $executed = 0;
    $errors = [];
    $executedStatements = [];
    
    foreach ($statements as $statement) {
        // Skip empty statements and comments
        if (empty($statement) || strpos(trim($statement), '--') === 0) {
            continue;
        }
        
        // Log what we're about to execute
        $statementPreview = substr(trim($statement), 0, 100) . (strlen($statement) > 100 ? '...' : '');
        error_log("Executing SQL: " . $statementPreview);
        
        try {
            if ($conn->query($statement)) {
                $executed++;
                $executedStatements[] = $statementPreview;
                error_log("✓ Success");
            } else {
                $error = $conn->error;
                $errors[] = [
                    'statement' => $statementPreview,
                    'error' => $error,
                    'errno' => $conn->errno
                ];
                error_log("✗ Error: " . $error);
            }
        } catch (Exception $e) {
            $errors[] = [
                'statement' => $statementPreview,
                'error' => $e->getMessage()
            ];
            error_log("✗ Exception: " . $e->getMessage());
        }
    }
    
    // Verify table was created
    $verifyTable = $conn->query("SHOW TABLES LIKE 'user_tokens'");
    $tableExists = ($verifyTable && $verifyTable->num_rows > 0);
    
    if (empty($errors)) {
        echo json_encode([
            'success' => true,
            'message' => 'Migration completed successfully',
            'statements_executed' => $executed,
            'executed_statements' => $executedStatements,
            'table_exists' => $tableExists,
            'database' => $currentDb['db_name']
        ]);
    } else {
        echo json_encode([
            'success' => $tableExists, // If table exists despite errors, still consider success
            'message' => $tableExists ? 'Migration completed with warnings' : 'Migration failed',
            'statements_executed' => $executed,
            'executed_statements' => $executedStatements,
            'errors' => $errors,
            'table_exists' => $tableExists,
            'database' => $currentDb['db_name']
        ]);
    }
    
} catch (Exception $e) {
    // Clear any output
    if (ob_get_length()) ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Flush output
ob_end_flush();
