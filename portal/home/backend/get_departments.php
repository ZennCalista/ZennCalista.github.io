<?php
// Get departments for dropdown
header('Content-Type: application/json');
include 'no_cache.php';
include '../db.php';

try {
    $stmt = $conn->prepare("SELECT department_id, department_name FROM departments ORDER BY department_name");
    $stmt->execute();
    $result = $stmt->get_result();

    $departments = [];
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }

    echo json_encode([
        'success' => true,
        'departments' => $departments
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>