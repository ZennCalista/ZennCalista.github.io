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

// Get proposal ID from query parameter
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing proposal ID']);
    exit;
}

try {
    // Delete the proposal
    $sql = "DELETE FROM program_proposals WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Proposal deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Proposal not found']);
        }
    } else {
        throw new Exception('Failed to delete proposal');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete proposal: ' . $e->getMessage()]);
}
?>