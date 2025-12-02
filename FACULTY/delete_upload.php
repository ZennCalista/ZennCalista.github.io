<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Not logged in";
    exit;
}

if (!isset($_POST['id'])) {
    echo "Missing upload ID";
    exit;
}

$user_id = $_SESSION['user_id'];
$upload_id = $_POST['id'];

// Get faculty_id from user_id
$faculty_id = null;
$stmt = $conn->prepare("SELECT id FROM faculty WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($faculty_id);
$stmt->fetch();
$stmt->close();

if (!$faculty_id) {
    echo "Faculty record not found";
    exit;
}

// Determine if it's a proposal or document deletion
// ID format: 'p_123' for proposal, 'd_123' for document
$is_proposal = strpos($upload_id, 'p_') === 0;
$actual_id = substr($upload_id, 2);

if (!is_numeric($actual_id)) {
    echo "Invalid ID format";
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    if ($is_proposal) {
        // Deleting a proposal - get all associated documents first
        $stmt = $conn->prepare("
            SELECT pp.faculty_id, pp.status, du.file_path, du.id as doc_id
            FROM program_proposals pp
            LEFT JOIN document_uploads du ON du.proposal_id = pp.id
            WHERE pp.id = ?
        ");
        $stmt->bind_param("i", $actual_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $results = [];
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
        $stmt->close();

        if (empty($results)) {
            echo "Upload not found";
            $conn->rollback();
            exit;
        }

        // Check ownership and status
        $proposal_faculty_id = $results[0]['faculty_id'];
        $proposal_status = $results[0]['status'];

        if ($proposal_faculty_id != $faculty_id) {
            echo "You don't have permission to delete this upload";
            $conn->rollback();
            exit;
        }

        if ($proposal_status !== 'pending') {
            echo "Only pending uploads can be deleted";
            $conn->rollback();
            exit;
        }

        // Delete all associated document files
        $upload_dir = __DIR__ . '/uploads/';
        foreach ($results as $row) {
            if (!empty($row['file_path'])) {
                $file_path = $upload_dir . basename($row['file_path']);
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
            }
        }

        // Delete documents from database
        $stmt = $conn->prepare("DELETE FROM document_uploads WHERE proposal_id = ?");
        $stmt->bind_param("i", $actual_id);
        $stmt->execute();
        $stmt->close();

        // Delete proposal from database
        $stmt = $conn->prepare("DELETE FROM program_proposals WHERE id = ?");
        $stmt->bind_param("i", $actual_id);
        $stmt->execute();
        $stmt->close();

    } else {
        // Deleting a single document
        $stmt = $conn->prepare("
            SELECT faculty_id, file_path, status, document_type
            FROM document_uploads
            WHERE id = ?
        ");
        $stmt->bind_param("i", $actual_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $document = $result->fetch_assoc();
        $stmt->close();

        if (!$document) {
            echo "Upload not found";
            $conn->rollback();
            exit;
        }

        // Check ownership and status
        if ($document['faculty_id'] != $faculty_id) {
            echo "You don't have permission to delete this upload";
            $conn->rollback();
            exit;
        }

        if ($document['status'] !== 'pending') {
            echo "Only pending uploads can be deleted";
            $conn->rollback();
            exit;
        }

        // Delete physical file
        $upload_dir = __DIR__ . '/uploads/';
        $file_path = $upload_dir . basename($document['file_path']);
        if (file_exists($file_path)) {
            @unlink($file_path);
        }

        // Delete from database
        $stmt = $conn->prepare("DELETE FROM document_uploads WHERE id = ?");
        $stmt->bind_param("i", $actual_id);
        $stmt->execute();
        $stmt->close();
    }

    // Commit transaction
    $conn->commit();
    echo "Upload deleted successfully!";

} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}
