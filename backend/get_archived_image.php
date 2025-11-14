<?php
include 'db.php';

$image_id = intval($_GET['image_id'] ?? 0);
if ($image_id <= 0) {
    http_response_code(400);
    exit('Invalid image ID');
}

$stmt = $conn->prepare("SELECT image_data FROM images_archive WHERE archive_image_id = ?");
$stmt->bind_param('i', $image_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    exit('Image not found');
}

$row = $result->fetch_assoc();
$stmt->close();
$conn->close();

header('Content-Type: image/jpeg'); // Default content type
header('Cache-Control: max-age=31536000'); // Cache for 1 year
echo $row['image_data'];
?>