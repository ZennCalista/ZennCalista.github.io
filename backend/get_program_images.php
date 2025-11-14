<?php
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Program ID required']);
    exit;
}

$programId = intval($_GET['id']);

try {
    $stmt = $conn->prepare("SELECT image_id, image_desc, description, image_type, image_size FROM images WHERE program_id = ? ORDER BY image_id");
    $stmt->bind_param("i", $programId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = [
            'id' => $row['image_id'],
            'description' => $row['image_desc'] ?: $row['description'],
            'type' => $row['image_type'],
            'size' => $row['image_size']
        ];
    }
    
    echo json_encode(['success' => true, 'images' => $images]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch images']);
}

$conn->close();
?>