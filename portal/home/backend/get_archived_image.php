<?php
// Serve archived image blobs from images_archive table
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    include 'no_cache.php';
    include '../db.php';

    if (!isset($_GET['image_id']) || empty($_GET['image_id'])) {
        http_response_code(400);
        echo "Image ID is required";
        exit();
    }

    $image_id = intval($_GET['image_id']);

    $sql = "SELECT image_data, image_desc FROM images_archive WHERE archive_image_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo "Database prepare error: " . $conn->error;
        exit();
    }

    $stmt->bind_param('i', $image_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image_data = $row['image_data'];

        if (empty($image_data)) {
            http_response_code(404);
            echo "Image data is empty";
            exit();
        }

        $image_info = getimagesizefromstring($image_data);
        if ($image_info !== false) {
            $mime_type = $image_info['mime'];
        } else {
            $mime_type = 'image/jpeg';
        }

        header('Content-Type: ' . $mime_type);
        header('Content-Length: ' . strlen($image_data));
        header('Cache-Control: public, max-age=3600');
        echo $image_data;
    } else {
        http_response_code(404);
        echo "Archived image not found";
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}

?>
