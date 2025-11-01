<?php
header('Content-Type: application/json');
include 'db.php';

// Start transaction
$conn->begin_transaction();

try {
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        throw new Exception('Invalid program ID');
    }
    
    // Get the program data
    $stmt = $conn->prepare("SELECT * FROM programs WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $program = $result->fetch_assoc();
    
    if (!$program) {
        throw new Exception('Program not found');
    }
    
    // Insert into programs_archive
    $stmt = $conn->prepare("INSERT INTO programs_archive 
        (program_name, project_titles, department_id, department, location, start_date, end_date, status, max_students, description, sdg_goals, faculty_id, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("ssisssssississs", 
        $program['program_name'],
        $program['project_titles'],
        $program['department_id'],
        $program['department'],
        $program['location'],
        $program['start_date'],
        $program['end_date'],
        $program['status'],
        $program['max_students'],
        $program['description'],
        $program['sdg_goals'],
        $program['faculty_id'],
        $program['created_at'],
        $program['updated_at']
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to archive program: ' . $stmt->error);
    }
    
    // Get the new archive ID
    $archive_id = $conn->insert_id;
    
    // Copy program images if they exist
    $img_result = $conn->query("SELECT * FROM program_images WHERE program_id = $id");
    if ($img_result && $img_result->num_rows > 0) {
        while ($img = $img_result->fetch_assoc()) {
            $img_stmt = $conn->prepare("INSERT INTO program_images (program_id, image_url, image_desc, uploaded_at) VALUES (?, ?, ?, ?)");
            $img_stmt->bind_param("isss", $archive_id, $img['image_url'], $img['image_desc'], $img['uploaded_at']);
            $img_stmt->execute();
        }
        // Delete original images
        $conn->query("DELETE FROM program_images WHERE program_id = $id");
    }
    
    // Delete the original program
    $stmt = $conn->prepare("DELETE FROM programs WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete original program: ' . $stmt->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Program archived successfully']);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>