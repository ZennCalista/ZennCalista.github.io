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
    // Get query parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
    $typeFilter = isset($_GET['type']) ? $_GET['type'] : '';
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
    $dateFilter = isset($_GET['date']) ? $_GET['date'] : '';
    $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

    // Calculate offset
    $offset = ($page - 1) * $limit;

    // Build WHERE conditions
    $whereConditions = [];
    $params = [];
    $types = '';

    if (!empty($typeFilter)) {
        $whereConditions[] = "document_type = ?";
        $params[] = $typeFilter;
        $types .= 's';
    }

    if (!empty($statusFilter)) {
        $whereConditions[] = "status = ?";
        $params[] = $statusFilter;
        $types .= 's';
    }

    if (!empty($dateFilter)) {
        $whereConditions[] = "DATE(upload_date) = ?";
        $params[] = $dateFilter;
        $types .= 's';
    }

    if (!empty($searchTerm)) {
        $searchCondition = "(original_filename LIKE ? OR file_path LIKE ? OR CAST(faculty_id AS CHAR) LIKE ? OR CAST(id AS CHAR) LIKE ? OR proposal_title LIKE ? OR description LIKE ?)";
        $searchParam = '%' . $searchTerm . '%';
        $whereConditions[] = $searchCondition;
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssssss';
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Query to get total count
    $countSql = "
        SELECT COUNT(*) as total
        FROM (
            SELECT id FROM document_uploads $whereClause
            UNION ALL
            SELECT id FROM program_proposals $whereClause
        ) as combined
    ";

    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countBindParams = array_merge([$types], $params);
        call_user_func_array([$countStmt, 'bind_param'], $countBindParams);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalCount = $countResult->fetch_assoc()['total'];
    $countStmt->close();

    // Query to get paginated data
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
        $whereClause

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
        $whereClause

        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($sql);
    $bindParams = array_merge([$types . 'ii'], $params, [$limit, $offset]);
    call_user_func_array([$stmt, 'bind_param'], $bindParams);

    $stmt->execute();
    $res = $stmt->get_result();

    $docs = [];
    while ($row = $res->fetch_assoc()) {
        $docs[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'data' => $docs,
        'total' => $totalCount,
        'page' => $page,
        'limit' => $limit,
        'totalPages' => ceil($totalCount / $limit)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch documents: ' . $e->getMessage()]);
}
?>