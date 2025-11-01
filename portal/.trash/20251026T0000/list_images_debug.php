<?php
// Backup copy of list_images_debug.php moved to .trash on 2025-10-26
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Restrict to localhost
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1', 'localhost'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

header('Content-Type: application/json');
try {
    include __DIR__ . '/no_cache.php';
    include __DIR__ . '/db.php';

    if ($conn->connect_error) {
        echo json_encode(['error' => 'DB connection failed: ' . $conn->connect_error]);
        exit();
    }

    $out = ['images' => [], 'images_archive' => []];

    // images table
    $res = $conn->query("SELECT image_id, program_id, CHAR_LENGTH(image_name) AS blob_length, image_desc FROM images ORDER BY image_id DESC LIMIT 200");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $out['images'][] = $r;
        }
    }

    // images_archive table if exists
    $check = $conn->query("SHOW TABLES LIKE 'images_archive'");
    if ($check && $check->num_rows > 0) {
        $res2 = $conn->query("SELECT archive_image_id, archive_program_id, CHAR_LENGTH(image_data) AS blob_length, image_desc FROM images_archive ORDER BY archive_image_id DESC LIMIT 200");
        if ($res2) {
            while ($r = $res2->fetch_assoc()) {
                $out['images_archive'][] = $r;
            }
        }
    }

    echo json_encode($out, JSON_PRETTY_PRINT);
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

?>
