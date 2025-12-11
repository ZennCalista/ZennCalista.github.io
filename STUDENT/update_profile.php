<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

// Validate required fields
if (empty($data['firstname']) || empty($data['lastname'])) {
    echo json_encode(['status' => 'error', 'message' => 'First name and last name are required']);
    exit;
}

if (empty($data['contact_no']) || empty($data['emergency_contact'])) {
    echo json_encode(['status' => 'error', 'message' => 'Contact number and emergency contact are required']);
    exit;
}

$firstname = trim($data['firstname']);
$mi = !empty($data['mi']) ? trim($data['mi']) : null;
$lastname = trim($data['lastname']);
$student_id = !empty($data['student_id']) ? trim($data['student_id']) : null;
$course = !empty($data['course']) ? trim($data['course']) : null;
$contact_no = trim($data['contact_no']);
$emergency_contact = trim($data['emergency_contact']);

// Check if student_id is being changed and if it's already taken by another user
if ($student_id) {
    $check_sql = "SELECT id FROM students WHERE student_id = ? AND user_id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('si', $student_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Student ID already exists']);
        $check_stmt->close();
        exit;
    }
    $check_stmt->close();
}

// Update the users table (firstname and lastname)
$user_sql = "UPDATE users SET firstname = ?, middle_initial = ?, lastname = ? WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param('sssi', $firstname, $mi, $lastname, $user_id);
$user_updated = $user_stmt->execute();
$user_stmt->close();

// Update the students table
$student_sql = "UPDATE students 
        SET student_id = ?, course = ?, contact_no = ?, emergency_contact = ? 
        WHERE user_id = ?";
$student_stmt = $conn->prepare($student_sql);
$student_stmt->bind_param('ssssi', $student_id, $course, $contact_no, $emergency_contact, $user_id);
$student_updated = $student_stmt->execute();
$student_stmt->close();

if ($user_updated && $student_updated) {
    echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update profile']);
}

$conn->close();