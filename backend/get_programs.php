<?php
header('Content-Type: application/json');
include 'db.php';

try {
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $includeArchived = isset($_GET['include_archived']) && ($_GET['include_archived'] === '1' || strtolower($_GET['include_archived']) === 'true');
    
    if ($includeArchived) {
        $result = $conn->query("SELECT * FROM programs ORDER BY created_at DESC");
    } else {
        $result = $conn->query("SELECT * FROM programs WHERE is_archived = 0 ORDER BY created_at DESC");
    }
    
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }
    
    $programs = [];
    while ($row = $result->fetch_assoc()) {
        $programs[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $programs]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>