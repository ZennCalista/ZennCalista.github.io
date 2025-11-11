<?php
/**
 * Get Department Carousel Images
 * Returns the newest 8 images from the images table for department carousel backgrounds
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once '../db.php';

try {
    // Check database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    // Query to get the newest 8 images (one for each department)
    // Order by image_id DESC to get the most recent uploads
    $sql = "SELECT image_id, image_desc FROM images ORDER BY image_id DESC LIMIT 8";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }
    
    $images = [];
    
    while ($row = $result->fetch_assoc()) {
        $images[] = [
            'id' => $row['image_id'],
            'src' => '../home/backend/get_image.php?image_id=' . $row['image_id'],
            'caption' => $row['image_desc'] ?: 'Image ' . $row['image_id']
        ];
    }
    
    // If no images found, return default placeholders
    if (empty($images)) {
        $images = [
            ['src' => '../images/download.jpg', 'caption' => 'Welcome to CvSU Extension Services'],
            ['src' => '../images/download1.jpg', 'caption' => 'Community Engagement Programs'],
            ['src' => '../images/download2.jpg', 'caption' => 'Extension Activities']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'images' => $images,
        'count' => count($images)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'images' => [
            ['src' => '../images/download.jpg', 'caption' => 'Welcome to CvSU Extension Services'],
            ['src' => '../images/download1.jpg', 'caption' => 'Community Engagement Programs'],
            ['src' => '../images/download2.jpg', 'caption' => 'Extension Activities']
        ]
    ]);
}
?>
