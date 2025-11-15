<?php
// Test script to verify admin authentication is working
header('Content-Type: application/json');
require_once 'db.php';
require_once '../backend/token_utils.php';

// Admin authentication check
function requireAdminAuth() {
    global $conn;
    session_start();

    // Check for token authentication first (multi-device support)
    $token = getTokenFromCookie();
    if ($token) {
        $tokenUser = validateToken($conn, $token);
        if ($tokenUser && in_array($tokenUser['role'], ['admin', 'faculty'])) {
            // Token is valid and user has admin/faculty role
            $_SESSION['user_id'] = $tokenUser['id'];
            $_SESSION['role'] = $tokenUser['role'];
            $_SESSION['user'] = $tokenUser;
            return [
                'authenticated' => true,
                'method' => 'token',
                'user' => $tokenUser
            ];
        }
    }

    // Fallback to session authentication
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        return [
            'authenticated' => false,
            'method' => 'none',
            'error' => 'No authentication found'
        ];
    }

    if (!in_array($_SESSION['role'], ['admin', 'faculty'])) {
        return [
            'authenticated' => false,
            'method' => 'session',
            'error' => 'Insufficient permissions',
            'role' => $_SESSION['role']
        ];
    }

    return [
        'authenticated' => true,
        'method' => 'session',
        'user_id' => $_SESSION['user_id'],
        'role' => $_SESSION['role']
    ];
}

// Test the authentication
$result = requireAdminAuth();
echo json_encode($result, JSON_PRETTY_PRINT);
?>