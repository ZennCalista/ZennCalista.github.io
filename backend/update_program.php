<?php
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        // Fallback to POST data
        $data = $_POST;
    }

    $program_id = $data['id'] ?? $_GET['id'] ?? '';
    $program_name = $data['program_name'] ?? '';
    $department_id = $data['department'] ?? '';
    $program_type = $data['program_type'] ?? '';
    $description = $data['description'] ?? '';
    $location = $data['location'] ?? '';
    $target_audience = $data['target_audience'] ?? '';
    $start_date = $data['start_date'] ?? '';
    $end_date = $data['end_date'] ?? '';
    $max_students = $data['max_students'] ?? 0;
    $status = $data['status'] ?? 'planning';

    // Look up department name from department_id
    $department_name = '';
    if (!empty($department_id)) {
        $dept_sql = "SELECT department_name FROM departments WHERE department_id = ?";
        $dept_stmt = $conn->prepare($dept_sql);
        $dept_stmt->bind_param("i", $department_id);
        $dept_stmt->execute();
        $dept_result = $dept_stmt->get_result();
        if ($dept_row = $dept_result->fetch_assoc()) {
            $department_name = $dept_row['department_name'];
        }
        $dept_stmt->close();
    }

    // Validate required fields
    if (empty($program_id) || empty($program_name) || empty($department_id) || empty($description) || 
        empty($location) || empty($start_date) || empty($end_date) || empty($max_students)) {
        throw new Exception('Please fill in all required fields');
    }

    // Update program
    $sql = "UPDATE programs SET 
        program_name = ?, department_id = ?, department = ?, program_type = ?, description = ?, 
        location = ?, target_audience = ?, start_date = ?, end_date = ?, max_students = ?, 
        status = ?, updated_at = NOW()
        WHERE id = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $stmt->bind_param("sisssssssisi",
        $program_name, $department_id, $department_name, $program_type, $description,
        $location, $target_audience, $start_date, $end_date, $max_students,
        $status, $program_id
    );

    if (!$stmt->execute()) {
        throw new Exception('Database execution error: ' . $stmt->error);
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Program updated successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>