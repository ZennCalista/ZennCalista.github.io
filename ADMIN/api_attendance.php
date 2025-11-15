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

// Add attendance entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add_attendance') {
    $student_name = $_POST['student_name'];
    $program_id = intval($_POST['program_id']);
    $status = $_POST['status'];
    $time_in = $_POST['time_in'];
    $date = $_POST['date'];

    $stmt = $conn->prepare("INSERT INTO attendance (student_name, program_id, status, time_in, date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisss", $student_name, $program_id, $status, $time_in, $date);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}

// Fetch all active programs for dropdown
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

// Fetch attendance logs
if (isset($_GET['action']) && $_GET['action'] === 'get_logs') {
    $result = $conn->query("SELECT a.*, p.program_name FROM attendance a LEFT JOIN programs p ON a.program_id = p.id ORDER BY a.date DESC, a.time_in DESC");
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $logs]);
    exit;
}

// Fetch program attendance summary
if (isset($_GET['action']) && $_GET['action'] === 'get_program_summary') {
    $result = $conn->query("
        SELECT 
            a.program_id,
            p.program_name,
            a.date,
            COUNT(a.id) as registered,
            SUM(a.status = 'Present') as present,
            SUM(a.status = 'Absent') as absent
        FROM attendance a
        LEFT JOIN programs p ON a.program_id = p.id
        GROUP BY a.program_id, a.date
        ORDER BY a.date DESC
    ");
    $summary = [];
    while ($row = $result->fetch_assoc()) {
        $row['attendance_percent'] = $row['registered'] > 0 ? round(($row['present'] / $row['registered']) * 100) : 0;
        $summary[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $summary]);
    exit;
}
?>