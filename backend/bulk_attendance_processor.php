<?php
/**
 * Bulk Attendance Processing
 *
 * This script provides functionality to:
 * 1. Mark all enrolled participants as absent for a session
 * 2. Update attendance records with actual check-ins
 * 3. Process attendance for multiple sessions at once
 */

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

// API endpoint to initialize bulk attendance for a session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    requireAdminAuth();

    $action = $_POST['action'];

    if ($action === 'initialize_session_attendance') {
        $session_id = intval($_POST['session_id']);

        // Get session details
        $session_query = "
            SELECT ps.*, p.program_name
            FROM program_sessions ps
            JOIN programs p ON ps.program_id = p.id
            WHERE ps.id = ?
        ";
        $stmt = $conn->prepare($session_query);
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $session = $stmt->get_result()->fetch_assoc();

        if (!$session) {
            echo json_encode(['success' => false, 'error' => 'Session not found']);
            exit;
        }

        // Get enrolled participants
        $enrolled_query = "
            SELECT u.id, u.firstname, u.lastname, u.email, u.role
            FROM participants part
            JOIN users u ON part.user_id = u.id
            WHERE part.program_id = ?
            AND part.status = 'accepted'
        ";
        $stmt = $conn->prepare($enrolled_query);
        $stmt->bind_param("i", $session['program_id']);
        $stmt->execute();
        $enrolled_result = $stmt->get_result();

        $participants = [];
        while ($row = $enrolled_result->fetch_assoc()) {
            $full_name = $row['firstname'] . ' ' . $row['lastname'];

            // Check if already has attendance record
            $check_query = "SELECT id, status FROM attendance WHERE session_id = ? AND student_name = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("is", $session_id, $full_name);
            $check_stmt->execute();
            $existing = $check_stmt->get_result()->fetch_assoc();

            $participants[] = [
                'user_id' => $row['id'],
                'name' => $full_name,
                'email' => $row['email'],
                'role' => $row['role'],
                'current_status' => $existing ? $existing['status'] : 'Not Recorded',
                'attendance_id' => $existing ? $existing['id'] : null
            ];
        }

        echo json_encode([
            'success' => true,
            'session' => $session,
            'participants' => $participants
        ]);
        exit;
    }

    if ($action === 'bulk_mark_absent') {
        $session_id = intval($_POST['session_id']);

        // Get enrolled participants who don't have attendance records
        $absent_query = "
            SELECT u.firstname, u.lastname, ps.program_id, ps.session_date
            FROM participants part
            JOIN users u ON part.user_id = u.id
            JOIN program_sessions ps ON part.program_id = ps.program_id
            WHERE ps.id = ?
            AND part.status = 'accepted'
            AND NOT EXISTS (
                SELECT 1 FROM attendance a
                WHERE a.session_id = ps.id
                AND a.student_name = CONCAT(u.firstname, ' ', u.lastname)
            )
        ";

        $stmt = $conn->prepare($absent_query);
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $absent_result = $stmt->get_result();

        $marked_count = 0;
        while ($row = $absent_result->fetch_assoc()) {
            $full_name = $row['firstname'] . ' ' . $row['lastname'];

            $insert_query = "
                INSERT INTO attendance (student_name, program_id, session_id, status, date, time_in, created_at)
                VALUES (?, ?, ?, 'Absent', ?, NOW(), NOW())
            ";

            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("siis", $full_name, $row['program_id'], $session_id, $row['session_date']);

            if ($insert_stmt->execute()) {
                $marked_count++;
            }
        }

        echo json_encode([
            'success' => true,
            'message' => "Marked {$marked_count} participants as absent"
        ]);
        exit;
    }

    if ($action === 'update_attendance_status') {
        $attendance_id = intval($_POST['attendance_id']);
        $new_status = $_POST['status'];
        $time_in = $_POST['time_in'] ?: date('H:i:s');

        $update_query = "UPDATE attendance SET status = ?, time_in = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssi", $new_status, $time_in, $attendance_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Attendance updated']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update attendance']);
        }
        exit;
    }

    if ($action === 'quick_checkin') {
        $session_id = intval($_POST['session_id']);
        $student_name = trim($_POST['student_name']);
        $status = $_POST['status'] ?: 'Present';
        $time_in = $_POST['time_in'] ?: date('H:i:s');

        // Get session details
        $session_query = "SELECT program_id, session_date FROM program_sessions WHERE id = ?";
        $stmt = $conn->prepare($session_query);
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $session = $stmt->get_result()->fetch_assoc();

        if (!$session) {
            echo json_encode(['success' => false, 'error' => 'Session not found']);
            exit;
        }

        // Check if already exists
        $check_query = "SELECT id FROM attendance WHERE session_id = ? AND student_name = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("is", $session_id, $student_name);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();

        if ($existing) {
            // Update existing
            $update_query = "UPDATE attendance SET status = ?, time_in = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ssi", $status, $time_in, $existing['id']);
            $update_stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Attendance updated']);
        } else {
            // Insert new
            $insert_query = "
                INSERT INTO attendance (student_name, program_id, session_id, status, date, time_in, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("siisss", $student_name, $session['program_id'], $session_id, $status, $session['session_date'], $time_in);
            $insert_stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Attendance recorded']);
        }
        exit;
    }
}

// Get sessions for bulk processing
if (isset($_GET['action']) && $_GET['action'] === 'get_sessions_for_bulk') {
    requireAdminAuth();

    $query = "
        SELECT ps.id, ps.session_title, ps.session_date, ps.session_start, ps.session_end, ps.location,
               p.program_name,
               COUNT(DISTINCT part.id) as enrolled_count,
               COUNT(DISTINCT a.id) as attendance_count
        FROM program_sessions ps
        JOIN programs p ON ps.program_id = p.id
        LEFT JOIN participants part ON p.id = part.program_id AND part.status = 'accepted'
        LEFT JOIN attendance a ON ps.id = a.session_id
        GROUP BY ps.id
        ORDER BY ps.session_date DESC, ps.session_start DESC
    ";

    $result = $conn->query($query);
    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }

    echo json_encode(['success' => true, 'sessions' => $sessions]);
    exit;
}

?>