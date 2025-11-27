<?php
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

// Insert or update notification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $message = $_POST['message'];
    $priority = $_POST['priority'];
    $audience = $_POST['audience'] ?? 'all';
    $expires_at = $_POST['expires_at'];
    $is_active = 1;

    if ($id) {
        $stmt = $conn->prepare("UPDATE notifications SET message=?, priority=?, audience=?, expires_at=? WHERE id=?");
        $stmt->bind_param("ssssi", $message, $priority, $audience, $expires_at, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO notifications (message, priority, audience, created_at, expires_at, is_active) VALUES (?, ?, ?, NOW(), ?, ?)");
        $stmt->bind_param("ssssi", $message, $priority, $audience, $expires_at, $is_active);
    }
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}

// Clear all notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_all') {
    $stmt = $conn->prepare("UPDATE notifications SET is_active = 0 WHERE is_active = 1 AND audience IN ('admin', 'all')");
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}

// Fetch notifications for admin tab
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['for'] === 'admin') {
    $result = $conn->query("SELECT * FROM notifications WHERE audience IN ('admin', 'all') AND is_active=1 ORDER BY created_at DESC");
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $notifications]);
    exit;
}

// Fetch notifications for faculty side
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['for'] === 'faculty') {
    $result = $conn->query("SELECT * FROM notifications WHERE audience IN ('faculty', 'all') AND is_active=1 AND expires_at >= CURDATE() ORDER BY created_at DESC");
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $notifications]);
    exit;
}
?>