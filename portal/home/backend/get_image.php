<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    include 'no_cache.php';
    include '../db.php';

    // Check if image_id is provided
    if (!isset($_GET['image_id']) || empty($_GET['image_id'])) {
        http_response_code(400);
        echo "Image ID is required";
        exit();
    }

    $image_id = intval($_GET['image_id']);

    // Query to get the BLOB image data - using image_id as primary key
    $sql = "SELECT image_name, image_desc FROM images WHERE image_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        http_response_code(500);
        echo "Database prepare error: " . $conn->error;
        exit();
    }
    
    $stmt->bind_param("i", $image_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image_data = $row['image_name']; // This is the BLOB data
        
        // Check if image data exists and is not empty
        if (empty($image_data)) {
            http_response_code(404);
            echo "Image data is empty";
            exit();
        }
        
        // Detect image type from BLOB data
        $image_info = getimagesizefromstring($image_data);
        if ($image_info !== false) {
            $mime_type = $image_info['mime'];
        } else {
            // Default to JPEG if we can't detect
            $mime_type = 'image/jpeg';
        }
        
        // Set appropriate headers
        header('Content-Type: ' . $mime_type);
        header('Content-Length: ' . strlen($image_data));
        header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
        
        // Output the image data
        echo $image_data;
    } else {
        // Image not found, return 404
        http_response_code(404);
        echo "Image not found";
    }

    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
?>
