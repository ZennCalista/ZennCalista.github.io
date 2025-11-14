<?php
// Get program ID
$programId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($programId <= 0) {
    header('HTTP/1.0 400 Bad Request');
    exit;
}

require_once 'db.php';

try {
    // Get image
    $stmt = $conn->prepare("SELECT image_data, image_type FROM program_images WHERE program_id = ? LIMIT 1");
    $stmt->bind_param("i", $programId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Set appropriate headers
        header('Content-Type: ' . $row['image_type']);
        header('Content-Length: ' . strlen($row['image_data']));
        header('Cache-Control: public, max-age=86400'); // Cache for 1 day

        // Output image data
        echo $row['image_data'];
    } else {
        // No image found, return 404
        header('HTTP/1.0 404 Not Found');
    }

    $stmt->close();

} catch (Exception $e) {
    error_log('Get program image error: ' . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
}

$conn->close();
?>