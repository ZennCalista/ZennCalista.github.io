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

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/token_utils.php';

// Set content type
header('Content-Type: application/json');

try {
    // Clean up expired tokens
    $deletedCount = cleanupExpiredTokens($conn);
    
    // Get statistics
    $stmt = $conn->query("SELECT COUNT(*) as active_tokens FROM user_tokens WHERE expires_at > NOW()");
    $stats = $stmt->fetch_assoc();
    $activeTokens = $stats['active_tokens'];
    
    $stmt2 = $conn->query("SELECT COUNT(DISTINCT user_id) as active_users FROM user_tokens WHERE expires_at > NOW()");
    $stats2 = $stmt2->fetch_assoc();
    $activeUsers = $stats2['active_users'];
    
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
