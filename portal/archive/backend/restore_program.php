<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Use __DIR__ relative path
    $db_path = __DIR__ . '/../../home/db.php';
    if (!file_exists($db_path)) throw new Exception('Database config not found');
    include $db_path;

    if ($conn->connect_error) throw new Exception('DB connection failed: ' . $conn->connect_error);

    $archive_id = intval($_GET['id'] ?? 0);
    if ($archive_id <= 0) throw new Exception('Invalid archive id');

    // Start transaction
    $conn->begin_transaction();

    // Get archive row
    $stmt = $conn->prepare('SELECT id, original_program_id FROM programs_archive WHERE id = ? LIMIT 1');
    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
    $stmt->bind_param('i', $archive_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $archive = $res->fetch_assoc();
    $stmt->close();

    if (!$archive) throw new Exception('Archive record not found');

    $original_id = isset($archive['original_program_id']) ? (int)$archive['original_program_id'] : 0;
    if ($original_id <= 0) {
        throw new Exception('Cannot restore: original_program_id missing for archive id ' . $archive_id);
    }

    // Unmark the original program as archived
    $u = $conn->prepare('UPDATE programs SET is_archived = 0, updated_at = NOW() WHERE id = ?');
    if (!$u) throw new Exception('Prepare failed: ' . $conn->error);
    $u->bind_param('i', $original_id);
    if (!$u->execute()) throw new Exception('Failed to unarchive program: ' . $u->error);
    $u->close();

    // Delete archived images for this archive
    $dimg = $conn->prepare('DELETE FROM images_archive WHERE archive_program_id = ?');
    if ($dimg) {
        $dimg->bind_param('i', $archive_id);
        $dimg->execute();
        $dimg->close();
    }

    // Delete the archive row
    $d = $conn->prepare('DELETE FROM programs_archive WHERE id = ?');
    if (!$d) throw new Exception('Prepare failed: ' . $conn->error);
    $d->bind_param('i', $archive_id);
    if (!$d->execute()) throw new Exception('Failed to delete archive row: ' . $d->error);
    $d->close();

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Program restored successfully']);

} catch (Exception $e) {
    if (isset($conn) && $conn) {
        try { $conn->rollback(); } catch (Exception $ex) {}
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>
