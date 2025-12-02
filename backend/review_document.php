<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

try {
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Check if admin is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['id']) || !isset($data['status'])) {
        throw new Exception('Invalid request data');
    }
    
    $id = intval($data['id']);
    $status = $conn->real_escape_string($data['status']);
    $remarks = isset($data['remarks']) ? $conn->real_escape_string($data['remarks']) : '';
    $admin_id = intval($_SESSION['user_id']);

    $sql = "UPDATE document_uploads SET status='$status', admin_remarks='$remarks', reviewed_by=$admin_id, reviewed_at=NOW() WHERE id=$id";
    
    if (!$conn->query($sql)) {
        throw new Exception('Failed to update document: ' . $conn->error);
    }
    
    if ($conn->affected_rows === 0) {
        throw new Exception('Document not found or no changes made');
    }

    echo json_encode(['success' => true, 'message' => 'Document reviewed successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>