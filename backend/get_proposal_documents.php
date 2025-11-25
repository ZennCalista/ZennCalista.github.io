<?php
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_GET['proposal_id'])) {
    echo json_encode([]);
    exit;
}

$proposal_id = $_GET['proposal_id'];

$sql = "SELECT id, original_filename, file_path, document_type, upload_date
        FROM document_uploads
        WHERE proposal_id = ? AND status = 'pending'
        ORDER BY upload_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $proposal_id);
$stmt->execute();
$result = $stmt->get_result();

$documents = [];
while ($row = $result->fetch_assoc()) {
    $documents[] = $row;
}

echo json_encode($documents);
?>