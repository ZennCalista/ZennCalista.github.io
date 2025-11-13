<?php
session_start();
header('Content-Type: application/json');
require_once '../FACULTY/db.php';

$user_id = $_SESSION['user_id'] ?? 0;

// Get student_name from users table
$stmt = $conn->prepare("SELECT firstname, lastname FROM users WHERE id=?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($fn, $ln);
$stmt->fetch();
$stmt->close();
$student_name = trim($fn . ' ' . $ln);

$program_id = $_GET['program_id'] ?? '';
$where = "student_name = ?";
$params = [$student_name];
$types = 's';

if ($program_id) {
    $where .= " AND program_id = ?";
    $params[] = $program_id;
    $types .= 'i';
}

$sql = "SELECT id, program_id, program_name, issue_date, certificate_file
        FROM certificates
        WHERE $where
        ORDER BY issue_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$certificates = [];
while ($row = $result->fetch_assoc()) {
    $certificates[] = [
        'program_name' => $row['program_name'],
        'certificate_date' => $row['issue_date'],
        'status' => 'Generated',
        'certificate_url' => $row['certificate_file'] ? $row['certificate_file'] : null
    ];
}
echo json_encode([
    'total' => count($certificates),
    'certificates' => $certificates
]);
$conn->close();
?>