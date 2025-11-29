<?php
// Simulate session for faculty user (user ID 18 corresponds to faculty ID 2)
$_SESSION['user_id'] = 18; // This would normally come from login

// Include the logic from my_uploads.php
require_once 'ADMIN/db.php';

$user_id = $_SESSION['user_id'];

// Get faculty_id for this user
$faculty_id = null;
$stmt = $conn->prepare("SELECT id FROM faculty WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($faculty_id);
$stmt->fetch();
$stmt->close();

if (!$faculty_id) {
    echo "Faculty record not found.\n";
    exit;
}

echo "Testing my_uploads.php for Faculty ID: $faculty_id\n\n";

// Fetch all uploads for this faculty - both proposals and documents
$uploads = [];

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

$proposal_count = 0;
while ($row = $proposals_result->fetch_assoc()) {
    $uploads[] = $row;
    $proposal_count++;
}
$stmt->close();

// Then, get all document uploads (including those linked to proposals)
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

$document_count = 0;
while ($row = $documents_result->fetch_assoc()) {
    $uploads[] = $row;
    $document_count++;
}
$stmt->close();

// Sort all uploads by date (most recent first)
usort($uploads, function($a, $b) {
    return strtotime($b['upload_date']) - strtotime($a['upload_date']);
});

echo "Found $proposal_count proposals and $document_count documents\n";
echo "Total uploads: " . count($uploads) . "\n\n";

echo "Sample of uploads (first 5):\n";
for ($i = 0; $i < min(5, count($uploads)); $i++) {
    $upload = $uploads[$i];
    echo ($i+1) . ". " . $upload['upload_type'] . ": " . substr($upload['title'], 0, 40) . "... | Status: " . $upload['status'] . " | Date: " . $upload['upload_date'] . "\n";
}

echo "\nTest completed successfully!\n";
?>