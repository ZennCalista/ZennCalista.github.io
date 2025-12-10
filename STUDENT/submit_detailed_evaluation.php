<?php
session_start();
header('Content-Type: application/json');
require_once '../FACULTY/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];

// Fetch student name
$user_query = $conn->prepare("SELECT firstname, lastname FROM users WHERE id = ?");
$user_query->bind_param('i', $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();
$student_name = $user['firstname'] . ' ' . $user['lastname'];

// Check if program has ended before allowing evaluation
$program_check = $conn->prepare("SELECT end_date FROM programs WHERE id = ?");
$program_check->bind_param('i', $data['program_id']);
$program_check->execute();
$program_result = $program_check->get_result();
$program = $program_result->fetch_assoc();

if (!$program || !$program['end_date'] || strtotime($program['end_date']) >= time()) {
    echo json_encode(['status' => 'error', 'message' => 'You can only evaluate programs that have ended.']);
    exit;
}

// Check if already evaluated
$eval_check = $conn->prepare("SELECT id FROM detailed_evaluations WHERE student_id = ? AND program_id = ?");
$eval_check->bind_param('ii', $user_id, $data['program_id']);
$eval_check->execute();
if ($eval_check->get_result()->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'You have already submitted an evaluation for this program.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO detailed_evaluations
  (program_id, student_id, student_name, content, facilitators, relevance, organization, experience, suggestion, recommend)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param(
  'iissiiiiis',
  $data['program_id'],
  $user_id,
  $student_name,
  $data['content'],
  $data['facilitators'],
  $data['relevance'],
  $data['organization'],
  $data['experience'],
  $data['suggestion'],
  $data['recommend']
);

if ($stmt->execute()) {
  echo json_encode(['status' => 'success', 'message' => 'Evaluation submitted!']);
} else {
  echo json_encode(['status' => 'error', 'message' => 'Failed to submit evaluation.']);
}
$conn->close();
?>