<?php
/**
 * Token Migration Runner
 * Creates the user_tokens table for multi-device authentication
 */

require_once 'db.php';

header('Content-Type: application/json');

try {
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
    
    foreach ($statements as $statement) {
        // Skip empty statements and comments
        if (empty($statement) || strpos(trim($statement), '--') === 0) {
            continue;
        }
        
        try {
            if ($conn->query($statement)) {
                $executed++;
            } else {
                $errors[] = $conn->error;
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
    
    if (empty($errors)) {
        echo json_encode([
            'success' => true,
            'message' => 'Migration completed successfully',
            'statements_executed' => $executed
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Migration completed with errors',
            'statements_executed' => $executed,
            'errors' => $errors
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
