<?php

session_start();
header('Content-Type: application/json');
require_once '../FACULTY/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['total_evaluations' => 0, 'programs' => []]);
    exit;
}

$user_id = $_SESSION['user_id'];
// Get student_name
$user_query = $conn->prepare("SELECT firstname, lastname FROM users WHERE id = ?");
$user_query->bind_param('i', $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();
$student_name = $user['firstname'] . ' ' . $user['lastname'];

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Get all programs the student is enrolled in
$sql = "SELECT p.id as program_id, p.program_name, e.status, p.end_date
        FROM enrollments e
        JOIN programs p ON e.program_id = p.id
        WHERE e.user_id = ? AND e.status = 'approved'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$programs = [];
$total_evals = 0;
while ($row = $result->fetch_assoc()) {
    // Check if evaluated in detailed_evaluations
    $eval = $conn->prepare("SELECT id, eval_date FROM detailed_evaluations WHERE student_id=? AND program_id=?");
    $eval->bind_param('ii', $user_id, $row['program_id']);
    $eval->execute();
    $eval_result = $eval->get_result();
    $evaluated = $eval_result->num_rows > 0;
    $submitted_date = '';
    if ($evaluated) {
        $eval_row = $eval_result->fetch_assoc();
        $submitted_date = $eval_row['eval_date'];
    }
    
    // Check if program has ended (end_date is in the past)
    $program_ended = false;
    if ($row['end_date']) {
        $program_ended = strtotime($row['end_date']) < time();
    }
    
    $programs[] = [
        'program_id' => $row['program_id'],
        'program_name' => $row['program_name'],
        'status' => $row['status'],
        'can_evaluate' => !$evaluated && $program_ended,
        'submitted_date' => $submitted_date,
        'evaluated' => $evaluated,
        'program_ended' => $program_ended
    ];
    if ($evaluated) $total_evals++;
}

// Sort: unevaluated programs first, then evaluated programs
usort($programs, function($a, $b) {
    // First sort by evaluation status (unevaluated first)
    if ($a['evaluated'] != $b['evaluated']) {
        return $a['evaluated'] ? 1 : -1;
    }
    // Then sort by program name alphabetically
    return strcmp($a['program_name'], $b['program_name']);
});

// Calculate pagination
$total_programs = count($programs);
$total_pages = ceil($total_programs / $items_per_page);
$paginated_programs = array_slice($programs, $offset, $items_per_page);

echo json_encode([
    'total_evaluations' => $total_evals,
    'programs' => $paginated_programs,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_programs' => $total_programs,
        'items_per_page' => $items_per_page
    ]
]);
$conn->close();
?>