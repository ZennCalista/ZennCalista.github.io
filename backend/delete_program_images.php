<?php
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['image_ids']) || !is_array($input['image_ids'])) {
    echo json_encode(['success' => false, 'message' => 'No image IDs provided']);
    exit;
}

$imageIds = array_map('intval', $input['image_ids']);

try {
    if (!empty($imageIds)) {
        $placeholders = str_repeat('?,', count($imageIds) - 1) . '?';
        $stmt = $conn->prepare("DELETE FROM images WHERE image_id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($imageIds)), ...$imageIds);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();
        
        echo json_encode(['success' => true, 'message' => "Deleted $deleted images"]);
    } else {
        echo json_encode(['success' => true, 'message' => 'No images to delete']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to delete images: ' . $e->getMessage()]);
}

$conn->close();
?>