<?php
session_start();
header('Content-Type: application/json');
require_once '../FACULTY/db.php';

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required']);
    exit;
}

$email = trim($data['email']);

// First, check if user already exists in users table
$check_existing = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check_existing->bind_param("s", $email);
$check_existing->execute();
$existing_result = $check_existing->get_result();

if ($existing_result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'An account with this email already exists. Please try logging in instead.']);
    $check_existing->close();
    exit;
}
$check_existing->close();

// Check master_students table first
$student_query = $conn->prepare("SELECT student_id, firstname, middle_initial, lastname, course FROM master_students WHERE email = ?");
$student_query->bind_param("s", $email);
$student_query->execute();
$student_result = $student_query->get_result();

if ($student_result->num_rows > 0) {
    $student_data = $student_result->fetch_assoc();
    $user_info = [
        'student_id' => $student_data['student_id'],
        'firstname' => $student_data['firstname'],
        'middle_initial' => $student_data['middle_initial'],
        'lastname' => $student_data['lastname'],
        'course' => $student_data['course'],
        'email' => $email
    ];

    echo json_encode([
        'status' => 'success',
        'role' => 'student',
        'user_info' => $user_info
    ]);
    $student_query->close();
    exit;
}
$student_query->close();

// Check master_faculty table
$faculty_query = $conn->prepare("SELECT faculty_id, firstname, middle_initial, lastname, department, position FROM master_faculty WHERE email = ?");
$faculty_query->bind_param("s", $email);
$faculty_query->execute();
$faculty_result = $faculty_query->get_result();

if ($faculty_result->num_rows > 0) {
    $faculty_data = $faculty_result->fetch_assoc();
    $user_info = [
        'faculty_id' => $faculty_data['faculty_id'],
        'firstname' => $faculty_data['firstname'],
        'middle_initial' => $faculty_data['middle_initial'],
        'lastname' => $faculty_data['lastname'],
        'department' => $faculty_data['department'],
        'position' => $faculty_data['position'],
        'email' => $email
    ];

    echo json_encode([
        'status' => 'success',
        'role' => 'faculty',
        'user_info' => $user_info
    ]);
    $faculty_query->close();
    exit;
}
$faculty_query->close();

// Email not found in master tables
echo json_encode([
    'status' => 'error',
    'message' => 'Your email was not found in our records. Please contact the administrator to add your information to the system.'
]);

$conn->close();
?>