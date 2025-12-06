<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Add CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include 'db.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: id and status']);
    exit;
}

$id = (int)$data['id'];
$status = $data['status'];
$remarks = $data['remarks'] ?? '';

try {
    // Get reviewer ID (default to 1 if not logged in)
    $reviewerId = $_SESSION['user_id'] ?? 1;

    // Update the proposal status
    $sql = "UPDATE program_proposals SET
            status = ?,
            review_notes = ?,
            reviewed_at = NOW(),
            reviewed_by = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssii', $status, $remarks, $reviewerId, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Proposal reviewed successfully']);
    } else {
        throw new Exception('Failed to update proposal');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to review proposal: ' . $e->getMessage()]);
}
?>