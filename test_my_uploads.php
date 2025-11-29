<?php
require_once 'ADMIN/db.php';

// Test with a faculty user (faculty_id = 2 has data)
$faculty_id = 2;

echo "Testing my_uploads query logic...\n\n";

// First, get all proposals
$proposals_sql = "SELECT pp.id, pp.proposal_title as title, pp.description, pp.status, pp.submitted_at as upload_date,
                         'proposal' as upload_type, NULL as file_path, NULL as original_filename,
                         NULL as program_name, pp.review_notes as admin_remarks
                  FROM program_proposals pp
                  WHERE pp.faculty_id = ?
                  ORDER BY pp.submitted_at DESC";
$stmt = $conn->prepare($proposals_sql);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$proposals_result = $stmt->get_result();

$uploads = [];
echo "Proposals found: " . $proposals_result->num_rows . "\n";
while ($row = $proposals_result->fetch_assoc()) {
    $uploads[] = $row;
    echo "- " . $row['title'] . " (" . $row['status'] . ")\n";
}
$stmt->close();

// Then, get all document uploads
$documents_sql = "SELECT du.id,
                         CASE
                           WHEN du.proposal_id IS NOT NULL THEN CONCAT('Proposal Document: ', pp.proposal_title)
                           WHEN du.program_id IS NOT NULL THEN p.program_name
                           ELSE 'General Document'
                         END as title,
                         du.document_type as description,
                         du.status,
                         du.upload_date,
                         CASE
                           WHEN du.proposal_id IS NOT NULL THEN 'proposal_document'
                           ELSE 'document'
                         END as upload_type,
                         du.file_path,
                         du.original_filename,
                         CASE
                           WHEN du.proposal_id IS NOT NULL THEN CONCAT('Proposal: ', pp.proposal_title)
                           WHEN du.program_id IS NOT NULL THEN p.program_name
                           ELSE 'General'
                         END as program_name,
                         NULL as admin_remarks
                  FROM document_uploads du
                  LEFT JOIN programs p ON du.program_id = p.id
                  LEFT JOIN program_proposals pp ON du.proposal_id = pp.id
                  WHERE du.faculty_id = ?
                  ORDER BY du.upload_date DESC";
$stmt = $conn->prepare($documents_sql);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$documents_result = $stmt->get_result();

echo "\nDocuments found: " . $documents_result->num_rows . "\n";
while ($row = $documents_result->fetch_assoc()) {
    $uploads[] = $row;
    echo "- " . $row['title'] . " (" . $row['upload_type'] . ", " . $row['status'] . ")\n";
}
$stmt->close();

// Sort all uploads by date
usort($uploads, function($a, $b) {
    return strtotime($b['upload_date']) - strtotime($a['upload_date']);
});

echo "\nTotal combined uploads: " . count($uploads) . "\n";
echo "Sorted by date (most recent first):\n";
foreach ($uploads as $upload) {
    echo "- " . $upload['upload_type'] . ": " . substr($upload['title'], 0, 50) . "... (" . $upload['status'] . ") - " . $upload['upload_date'] . "\n";
}

echo "\nTest completed!\n";
?>