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

// Enhanced authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    returnJsonError('Unauthorized: Please log in as a student');
}

$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    returnJsonError('Invalid JSON input');
}

$program_id = $input['program_id'] ?? null;
$reason = $input['reason'] ?? '';

if (!$program_id) {
    returnJsonError('Program ID is required');
}

require_once '../FACULTY/db.php';
if ($conn->connect_error) {
    returnJsonError('Database connection failed');
}

$user_id = $_SESSION['user_id'];

// Check if program exists and is active
$program_check = $conn->prepare("SELECT id, program_name, status, max_students FROM programs WHERE id = ? AND status = 'ongoing'");
$program_check->bind_param('i', $program_id);
$program_check->execute();
$program_result = $program_check->get_result();

if ($program_result->num_rows === 0) {
    $program_check->close();
    $conn->close();
    returnJsonError('Program not found or not accepting enrollments');
}

$program_data = $program_result->fetch_assoc();
$program_check->close();

// Check current enrollment count
$count_stmt = $conn->prepare("SELECT COUNT(*) as current_count FROM enrollments WHERE program_id = ? AND status = 'approved'");
$count_stmt->bind_param('i', $program_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$current_count = $count_result->fetch_assoc()['current_count'];
$count_stmt->close();

if ($current_count >= $program_data['max_students']) {
    $conn->close();
    returnJsonError('Program is at full capacity');
}

// Check for existing enrollment
$stmt = $conn->prepare("SELECT id, status FROM enrollments WHERE user_id = ? AND program_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param('ii', $user_id, $program_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'pending') {
        $stmt->close();
        $conn->close();
        returnJsonError('You already have a pending enrollment for this program');
    } elseif ($row['status'] === 'approved') {
        $stmt->close();
        $conn->close();
        returnJsonError('You are already enrolled in this program');
    }
}
$stmt->close();

// Insert new enrollment
$stmt = $conn->prepare("INSERT INTO enrollments (user_id, program_id, reason, status, enrollment_date) VALUES (?, ?, ?, 'pending', NOW())");
$stmt->bind_param('iis', $user_id, $program_id, $reason);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    echo json_encode([
        'status' => 'success', 
        'message' => 'Enrollment request submitted successfully! Your application is pending approval.',
        'program_name' => $program_data['program_name']
    ]);
} else {
    $stmt->close();
    $conn->close();
    returnJsonError('Failed to submit enrollment request. Please try again.');
}
?>