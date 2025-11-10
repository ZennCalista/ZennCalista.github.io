<?php
// Disable display_errors to prevent HTML output that breaks JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header first to ensure proper content type
header('Content-Type: application/json');

try {
    include 'no_cache.php';
    include '../db.php';

    // Check database connection
    if (!$conn || $conn->connect_error) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit();
    }

    // Get program ID from query parameter
    $programId = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($programId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid program ID']);
        exit();
    }

    // Query to get program details with images
    // Fixed JOIN: images.program_id references programs.id
    $sql = "SELECT 
            p.id, p.program_name as title, p.description, p.department_id,
            p.project_titles, p.location, p.start_date, p.end_date, 
            p.status, p.max_students, p.sdg_goals,
            d.department_name,
            GROUP_CONCAT(i.image_id ORDER BY i.image_id ASC SEPARATOR '||') as image_ids,
            GROUP_CONCAT(COALESCE(i.image_desc, 'Program image') ORDER BY i.image_id ASC SEPARATOR '||') as image_descs
        FROM programs p
        LEFT JOIN departments d ON p.department_id = d.department_id
        LEFT JOIN images i ON p.id = i.program_id
        WHERE p.id = ? AND (p.is_archived = 0 OR p.is_archived IS NULL)
        GROUP BY p.id, p.program_name, p.description, p.department_id,
                 p.project_titles, p.location, p.start_date, p.end_date, 
                 p.status, p.max_students, p.sdg_goals, d.department_name";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $programId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Program not found']);
        exit();
    }

    $row = $result->fetch_assoc();
    $images = [];

    // Parse the concatenated image data
    if (!empty($row['image_ids'])) {
        $image_ids = explode('||', $row['image_ids']);
        $image_descs = explode('||', $row['image_descs']);
        
        // Detect environment: Local vs Hosted
        // For local XAMPP: Use relative path from portal/home/
        // For production: Use root-relative path
        $is_local = isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/Etracker/') !== false;
        $base_url = $is_local ? '../../uploads/' : '/uploads/';
        
        foreach ($image_ids as $idx => $image_id) {
            if (!empty($image_id)) {
                $images[] = [
                    'id' => $image_id,
                    'path' => $base_url . $image_id . '.jpg',
                    'description' => isset($image_descs[$idx]) ? $image_descs[$idx] : 'Program image'
                ];
            }
        }
    }

    // Build program object
    $program = [
        'id' => $row['id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'department_id' => $row['department_id'],
        'department_name' => $row['department_name'],
        'project_titles' => $row['project_titles'],
        'location' => $row['location'],
        'start_date' => $row['start_date'],
        'end_date' => $row['end_date'],
        'status' => $row['status'],
        'max_students' => $row['max_students'],
        'sdg_goals' => $row['sdg_goals'],
        'images' => $images
    ];

    echo json_encode(['success' => true, 'program' => $program]);

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>