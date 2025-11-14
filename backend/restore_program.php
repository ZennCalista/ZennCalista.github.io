<?php
header('Content-Type: application/json');
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$archive_id = intval($_POST['id'] ?? 0);
if ($archive_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid archive ID']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Get archive row
    $stmt = $conn->prepare('SELECT id, original_program_id FROM programs_archive WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $archive_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $archive = $res->fetch_assoc();
    $stmt->close();

    if (!$archive) {
        $conn->rollback();
        echo json_encode(['success' => true, 'message' => 'Program already restored']);
        exit;
    }

    $original_id = $archive['original_program_id'];
    if ($original_id <= 0) {
        throw new Exception('Cannot restore: original_program_id missing');
    }

    // Check if the original program is already restored
    $check_stmt = $conn->prepare('SELECT is_archived FROM programs WHERE id = ?');
    $check_stmt->bind_param('i', $original_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows > 0) {
        $row = $check_result->fetch_assoc();
        if ($row['is_archived'] == 0) {
            $check_stmt->close();
            $conn->rollback();
            echo json_encode(['success' => true, 'message' => 'Program already restored']);
            exit;
        }
    }
    $check_stmt->close();

    // Unmark the original program as archived
    $u = $conn->prepare('UPDATE programs SET is_archived = 0, updated_at = NOW() WHERE id = ?');
    $u->bind_param('i', $original_id);
    if (!$u->execute()) throw new Exception('Failed to unarchive program: ' . $u->error);
    $u->close();

    // Delete archived images
    $dimg = $conn->prepare('DELETE FROM images_archive WHERE archive_program_id = ?');
    $dimg->bind_param('i', $archive_id);
    $dimg->execute();
    $dimg->close();

    // Delete the archive row
    $d = $conn->prepare('DELETE FROM programs_archive WHERE id = ?');
    $d->bind_param('i', $archive_id);
    if (!$d->execute()) throw new Exception('Failed to delete archive row: ' . $d->error);
    $d->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Program restored successfully']);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>