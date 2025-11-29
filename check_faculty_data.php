<?php
require_once 'ADMIN/db.php';

$result = $conn->query('SELECT f.id, u.firstname, u.lastname FROM faculty f LEFT JOIN users u ON f.user_id = u.id ORDER BY f.id');
echo "Available faculty members:\n";
while ($row = $result->fetch_assoc()) {
    echo 'Faculty ID: ' . $row['id'] . ' - ' . $row['firstname'] . ' ' . $row['lastname'] . "\n";
}

// Check proposals
echo "\nProposals:\n";
$result = $conn->query('SELECT pp.id, pp.faculty_id, pp.proposal_title, pp.status FROM program_proposals pp ORDER BY pp.id');
while ($row = $result->fetch_assoc()) {
    echo 'Proposal ID: ' . $row['id'] . ' - Faculty: ' . $row['faculty_id'] . ' - Title: ' . substr($row['proposal_title'], 0, 30) . '... - Status: ' . $row['status'] . "\n";
}

// Check documents
echo "\nDocuments:\n";
$result = $conn->query('SELECT du.id, du.faculty_id, du.document_type, du.proposal_id, du.program_id, du.status FROM document_uploads du ORDER BY du.id');
while ($row = $result->fetch_assoc()) {
    echo 'Document ID: ' . $row['id'] . ' - Faculty: ' . $row['faculty_id'] . ' - Type: ' . $row['document_type'] . ' - Proposal: ' . ($row['proposal_id'] ?? 'NULL') . ' - Program: ' . ($row['program_id'] ?? 'NULL') . ' - Status: ' . $row['status'] . "\n";
}
?>