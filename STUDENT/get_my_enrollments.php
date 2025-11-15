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

require_once '../FACULTY/db.php';
if ($conn->connect_error) {
    returnJsonError('Database connection failed: ' . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT program_id, status FROM enrollments WHERE user_id = ? AND (status = 'pending' OR status = 'approved')";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    returnJsonError('Failed to prepare statement: ' . $conn->error);
}
$stmt->bind_param('i', $user_id);
if (!$stmt->execute()) {
    returnJsonError('Failed to execute query: ' . $stmt->error);
}
$result = $stmt->get_result();

$enrolled = [];
while ($row = $result->fetch_assoc()) {
    $enrolled[$row['program_id']] = $row['status'];
}
echo json_encode(['status' => 'success', 'enrolled' => $enrolled]);
?>