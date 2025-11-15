<?php
header('Content-Type: application/json');
require_once 'db.php';

// Public endpoint - no authentication required for registration
if (isset($_GET['action']) && $_GET['action'] === 'get_departments') {
    $stmt = $conn->prepare("SELECT department_name FROM departments ORDER BY department_name");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'SQL Error: ' . $conn->error]);
        exit;
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $departments = [];
    while ($row = $res->fetch_assoc()) {
        $departments[] = $row['department_name'];
    }
    echo json_encode(['success' => true, 'data' => $departments]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>