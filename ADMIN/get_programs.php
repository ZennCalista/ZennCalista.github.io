<?php
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
            return true;
        }
    }

    // Fallback to session authentication
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }

    if (!in_array($_SESSION['role'], ['admin', 'faculty'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Admin or faculty access required']);
        exit;
    }

    return true;
}

// Require admin authentication for all operations
requireAdminAuth();

// Fetch all active programs for dropdowns
if (isset($_GET['action']) && $_GET['action'] === 'get_programs') {
    $now = date('Y-m-d');
    $result = $conn->prepare("SELECT id, program_name FROM programs WHERE start_date <= ? AND end_date >= ?");
    $result->bind_param("ss", $now, $now);
    $result->execute();
    $res = $result->get_result();
    $programs = [];
    while ($row = $res->fetch_assoc()) {
        $programs[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $programs]);
    exit;
}