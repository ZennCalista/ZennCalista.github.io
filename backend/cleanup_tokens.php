<?php
/**
 * Token Cleanup Script
 * Removes expired authentication tokens from the database
 * 
 * This script should be run periodically (e.g., via cron job or manual trigger)
 * to keep the database clean and maintain performance
 * 
 * Usage:
 * - Via browser: https://your-domain.com/backend/cleanup_tokens.php
 * - Via CLI: php cleanup_tokens.php
 * - Via cron: 0 3 * * * php /path/to/cleanup_tokens.php
 */

// Start output buffering to prevent any unwanted output
ob_start();

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Set content type first
header('Content-Type: application/json');

try {
    // Try to include database connection
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/token_utils.php';
    
    // Clear any buffered output before sending JSON
    ob_clean();
    
    // Check if connection was successful
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection not available");
    }
    // Check if user_tokens table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'user_tokens'");
    
    if ($tableCheck->num_rows === 0) {
        // Table doesn't exist yet
        echo json_encode([
            'success' => false,
            'error' => 'user_tokens table does not exist. Please run the migration first.',
            'deleted_tokens' => 0,
            'active_tokens' => 0,
            'active_users' => 0,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Clean up expired tokens
    $deletedCount = cleanupExpiredTokens($conn);
    
    // Get statistics
    $stmt = $conn->query("SELECT COUNT(*) as active_tokens FROM user_tokens WHERE expires_at > NOW()");
    if (!$stmt) {
        throw new Exception("Failed to get active tokens count: " . $conn->error);
    }
    $stats = $stmt->fetch_assoc();
    $activeTokens = $stats['active_tokens'] ?? 0;
    
    $stmt2 = $conn->query("SELECT COUNT(DISTINCT user_id) as active_users FROM user_tokens WHERE expires_at > NOW()");
    if (!$stmt2) {
        throw new Exception("Failed to get active users count: " . $conn->error);
    }
    $stats2 = $stmt2->fetch_assoc();
    $activeUsers = $stats2['active_users'] ?? 0;
    
    error_log("Token cleanup completed: deleted={$deletedCount}, active_tokens={$activeTokens}, active_users={$activeUsers}");
    
    echo json_encode([
        'success' => true,
        'deleted_tokens' => $deletedCount,
        'active_tokens' => $activeTokens,
        'active_users' => $activeUsers,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Token cleanup error: " . $e->getMessage());
    
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'deleted_tokens' => 0,
        'active_tokens' => 0,
        'active_users' => 0
    ]);
} catch (Throwable $e) {
    // Catch any fatal errors
    error_log("Token cleanup fatal error: " . $e->getMessage());
    
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fatal error: ' . $e->getMessage(),
        'deleted_tokens' => 0,
        'active_tokens' => 0,
        'active_users' => 0
    ]);
}

// Flush output buffer
ob_end_flush();
