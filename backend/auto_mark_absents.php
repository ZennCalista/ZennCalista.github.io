<?php
/**
 * Automatic Absent Creation Script
 *
 * This script compares enrolled participants against attendance records for each session
 * and automatically marks enrolled participants as absent if they haven't checked in.
 *
 * Usage: Run this script after program sessions to automatically mark absents
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
        die("Authentication required\n");
    }

    if (!in_array($_SESSION['role'], ['admin', 'faculty'])) {
        die("Admin or faculty access required\n");
    }

    return true;
}

// Require admin authentication
requireAdminAuth();

echo "Starting automatic absent marking process...\n";

// Get all program sessions that have ended (past sessions)
$current_date = date('Y-m-d');
$current_time = date('H:i:s');

$sessions_query = "
    SELECT ps.id as session_id, ps.program_id, ps.session_title, ps.session_date, ps.session_start, ps.session_end,
           p.program_name
    FROM program_sessions ps
    JOIN programs p ON ps.program_id = p.id
    WHERE (ps.session_date < '$current_date')
       OR (ps.session_date = '$current_date' AND ps.session_end < '$current_time')
    ORDER BY ps.session_date DESC, ps.session_end DESC
";

$sessions_result = $conn->query($sessions_query);
$sessions = [];
while ($row = $sessions_result->fetch_assoc()) {
    $sessions[] = $row;
}

echo "Found " . count($sessions) . " completed sessions to process\n";

$total_absents_marked = 0;

// Process each session
foreach ($sessions as $session) {
    echo "\nProcessing session: {$session['program_name']} - {$session['session_title']} ({$session['session_date']})\n";

    // Get all enrolled participants for this program
    $enrolled_query = "
        SELECT u.id, u.firstname, u.lastname, u.email, u.role
        FROM participants part
        JOIN users u ON part.user_id = u.id
        WHERE part.program_id = {$session['program_id']}
        AND part.status = 'accepted'
    ";

    $enrolled_result = $conn->query($enrolled_query);
    $enrolled_participants = [];
    while ($row = $enrolled_result->fetch_assoc()) {
        $enrolled_participants[] = $row;
    }

    echo "Found " . count($enrolled_participants) . " enrolled participants\n";

    // Check attendance for this session
    $attendance_query = "
        SELECT student_name, status
        FROM attendance
        WHERE session_id = {$session['session_id']}
    ";

    $attendance_result = $conn->query($attendance_query);
    $attendance_records = [];
    while ($row = $attendance_result->fetch_assoc()) {
        $attendance_records[] = $row;
    }

    echo "Found " . count($attendance_records) . " attendance records\n";

    // Mark absents for enrolled participants who haven't checked in
    foreach ($enrolled_participants as $participant) {
        $full_name = $participant['firstname'] . ' ' . $participant['lastname'];
        $already_recorded = false;

        // Check if this participant already has an attendance record for this session
        foreach ($attendance_records as $record) {
            if (strtolower(trim($record['student_name'])) === strtolower(trim($full_name))) {
                $already_recorded = true;
                break;
            }
        }

        if (!$already_recorded) {
            // Mark as absent
            $insert_query = "
                INSERT INTO attendance (student_name, program_id, session_id, status, date, time_in, created_at)
                VALUES (?, ?, ?, 'Absent', ?, NOW(), NOW())
            ";

            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("siis", $full_name, $session['program_id'], $session['session_id'], $session['session_date']);

            if ($stmt->execute()) {
                echo "  ✓ Marked {$full_name} as absent\n";
                $total_absents_marked++;
            } else {
                echo "  ✗ Failed to mark {$full_name} as absent: " . $conn->error . "\n";
            }
        }
    }
}

echo "\nProcess completed!\n";
echo "Total absents automatically marked: {$total_absents_marked}\n";

?>