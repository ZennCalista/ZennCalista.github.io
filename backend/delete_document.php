<?php
header('Content-Type: application/json');
include 'db.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid document ID']);
    exit;
}

// Check if document exists and get its details
$res = $conn->query("SELECT file_path, document_type, proposal_id FROM document_uploads WHERE id=$id");
if (!$res || $res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Document not found']);
    exit;
}

$row = $res->fetch_assoc();

// Delete the physical file if it exists
$file = '../FACULTY/' . $row['file_path'];
if (file_exists($file)) {
    unlink($file);
}

// For proposal documents, we need to handle the foreign key constraint
// Since fk_proposal_id has ON DELETE NO ACTION, we need to clear the proposal_id first
if ($row['proposal_id']) {
    $conn->query("UPDATE document_uploads SET proposal_id = NULL WHERE id=$id");
}

// Now delete the document record
$result = $conn->query("DELETE FROM document_uploads WHERE id=$id");

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete document from database']);
}
?>