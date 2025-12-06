<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Add CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

try {
    // Query to get both document uploads and program proposals
    $sql = "
        SELECT
            'document' as record_type,
            id,
            program_id,
            faculty_id,
            document_type COLLATE utf8mb4_unicode_ci as document_type,
            file_path COLLATE utf8mb4_unicode_ci as file_path,
            original_filename COLLATE utf8mb4_unicode_ci as original_filename,
            upload_date,
            status COLLATE utf8mb4_unicode_ci as status,
            uploaded_by,
            created_at,
            NULL as proposal_title,
            NULL as description,
            NULL as submitted_at,
            NULL as review_notes
        FROM document_uploads

        UNION ALL

        SELECT
            'proposal' as record_type,
            id,
            program_id,
            faculty_id,
            'proposal' COLLATE utf8mb4_unicode_ci as document_type,
            NULL as file_path,
            proposal_title COLLATE utf8mb4_unicode_ci as original_filename,
            DATE(submitted_at) as upload_date,
            status COLLATE utf8mb4_unicode_ci as status,
            faculty_id as uploaded_by,
            submitted_at as created_at,
            proposal_title COLLATE utf8mb4_unicode_ci as proposal_title,
            description COLLATE utf8mb4_unicode_ci as description,
            submitted_at,
            review_notes COLLATE utf8mb4_unicode_ci as review_notes
        FROM program_proposals

        ORDER BY created_at DESC
    ";

    $res = $conn->query($sql);

    if (!$res) {
        throw new Exception('SQL Error: ' . $conn->error);
    }

    $docs = [];
    while ($row = $res->fetch_assoc()) {
        $docs[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $docs]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch documents: ' . $e->getMessage()]);
}
?>