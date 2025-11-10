<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Use __DIR__ relative path
    $db_path = __DIR__ . '/../../home/db.php';
    if (!file_exists($db_path)) throw new Exception('Database config not found');
    include $db_path;
    
    // Include cache helper to invalidate cache after restoring
    require_once __DIR__ . '/../../home/backend/cache_helper.php';
    $cache = new SimpleCache(__DIR__ . '/../../home/backend/cache');

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

    if (!$archive) {
        // Archive already deleted/restored - return success to avoid errors on duplicate clicks
        $conn->rollback();
        echo json_encode(['success' => true, 'message' => 'Program already restored']);
        exit;
    }

    $original_id = isset($archive['original_program_id']) ? (int)$archive['original_program_id'] : 0;
    if ($original_id <= 0) {
        throw new Exception('Cannot restore: original_program_id missing for archive id ' . $archive_id);
    }
    
    // Check if the original program is already unarchived (prevent duplicate restore)
    $check_stmt = $conn->prepare('SELECT is_archived FROM programs WHERE id = ?');
    if ($check_stmt) {
        $check_stmt->bind_param('i', $original_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $row = $check_result->fetch_assoc();
            if ($row['is_archived'] == 0) {
                // Already restored
                $check_stmt->close();
                $conn->rollback();
                echo json_encode(['success' => true, 'message' => 'Program already restored']);
                exit;
            }
        }
        $check_stmt->close();
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
    
    // Invalidate cache for both home page and archive page
    $cache->delete('programs_list_v3');
    $cache->delete('archived_programs_list_v3');

    echo json_encode(['success' => true, 'message' => 'Program restored successfully']);

} catch (Exception $e) {
    if (isset($conn) && $conn) {
        try { $conn->rollback(); } catch (Exception $ex) {}
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>
