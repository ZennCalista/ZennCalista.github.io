<?php

session_start();
header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(E_ALL);

// Function to return JSON error
function returnJsonError($message) {
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    returnJsonError('Unauthorized: Please log in as a student');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    returnJsonError('Invalid program ID');
}

$program_id = (int)$_GET['id'];

require_once 'db.php';
if ($conn->connect_error) {
    returnJsonError('Database connection failed: ' . $conn->connect_error);
}

$sql = "SELECT
            p.id,
            p.program_name,
            p.start_date,
            p.end_date,
            p.department,
            p.location,
            p.max_students,
            p.description,
            p.status,
            u.firstname AS faculty_firstname,
            u.lastname AS faculty_lastname
        FROM programs p
        LEFT JOIN faculty f ON p.faculty_id = f.id
        LEFT JOIN users u ON f.user_id = u.id
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    returnJsonError('Query preparation failed: ' . $conn->error);
}

$stmt->bind_param('i', $program_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    returnJsonError('Program not found');
}

$row = $result->fetch_assoc();
$stmt->close();

// Get sessions
$sessions = [];
$sess_sql = "SELECT session_title, session_date, session_start, session_end, location FROM program_sessions WHERE program_id = ? ORDER BY session_date, session_start";
$sess_stmt = $conn->prepare($sess_sql);
$sess_stmt->bind_param('i', $program_id);
$sess_stmt->execute();
$sess_result = $sess_stmt->get_result();
while ($sess_row = $sess_result->fetch_assoc()) {
    $sessions[] = $sess_row;
}
$sess_stmt->close();

$row['faculty_name'] = trim($row['faculty_firstname'] . ' ' . $row['faculty_lastname']);
$row['sessions'] = $sessions;
unset($row['faculty_firstname'], $row['faculty_lastname']);

echo json_encode(['status' => 'success', 'program' => $row]);
$conn->close();
?>