<?php

header('Content-Type: application/json');
require_once 'db.php';
require_once 'token_utils.php';

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

// Total Students
$students = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student'")->fetch_assoc()['total'];

// Total Non-Acad Users
$non_acad = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='non_acad'")->fetch_assoc()['total'];

// Total Faculty
$faculty = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='faculty'")->fetch_assoc()['total'];

// Ongoing Programs
$programs = $conn->query("SELECT COUNT(*) as total FROM programs WHERE status='ongoing'")->fetch_assoc()['total'];

// Certificates Issued (count all participants as issued)
$certificates = $conn->query("SELECT COUNT(*) as total FROM participants")->fetch_assoc()['total'];

// Attendance Rate (placeholder, since no attendance table)
$attendance = 85; // Placeholder value

// Upcoming Sessions (use program start_date)
$sessions = [];
$res = $conn->query("SELECT start_date as date, program_name 
    FROM programs 
    WHERE start_date >= CURDATE() 
    ORDER BY start_date ASC LIMIT 3");
while ($row = $res->fetch_assoc()) $sessions[] = $row;

// Feedback Highlights (placeholder, since no detailed_evaluations)
$feedback = ["The program was well-organized.", "Great learning experience.", "Looking forward to more sessions."];

// Program Trends (enrollment from participants)
$trends = ['labels'=>[], 'data'=>[]];
$res = $conn->query("SELECT p.program_name, COUNT(part.program_id) as enrolled 
    FROM programs p 
    LEFT JOIN participants part ON p.id = part.program_id 
    GROUP BY p.id 
    ORDER BY enrolled DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
    $trends['labels'][] = $row['program_name'];
    $trends['data'][] = (int)$row['enrolled'];
}

echo json_encode([
    'students' => (int)$students,
    'non_acad' => (int)$non_acad,
    'faculty' => (int)$faculty,
    'programs' => (int)$programs,
    'certificates' => (int)$certificates,
    'attendanceRate' => round($attendance),
    'upcomingSessions' => $sessions,
    'feedback' => $feedback,
    'programTrends' => $trends
]);