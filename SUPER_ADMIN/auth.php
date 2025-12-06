<?php
// Authentication helper for Super Admin pages
require_once 'db.php';
require_once '../backend/token_utils.php';

function requireSuperAdminAuth() {
    global $conn;
    session_start();

    // Check for token authentication first (multi-device support)
    $token = getTokenFromCookie();
    if ($token) {
        $tokenUser = validateToken($conn, $token);
        if ($tokenUser && $tokenUser['role'] === 'super_admin') {
            // Token is valid and user has super_admin role
            $_SESSION['user_id'] = $tokenUser['id'];
            $_SESSION['role'] = $tokenUser['role'];
            $_SESSION['user'] = $tokenUser;
            return true;
        }
    }

    // Fallback to session authentication
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }

    if ($_SESSION['role'] !== 'super_admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Super Admin access required']);
        exit;
    }

    return true;
}

// Helper function to prevent write operations
function preventWriteOperations() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Super Admin has view-only access']);
        exit;
    }
}
?>
