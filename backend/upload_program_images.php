<?php
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if files were uploaded
if (!isset($_FILES['images']) || !isset($_POST['program_id'])) {
    echo json_encode(['success' => false, 'message' => 'No images or program ID provided']);
    exit;
}

$programId = intval($_POST['program_id']);
$files = $_FILES['images'];
$descriptions = $_POST['image_descriptions'] ?? [];

$uploadedImages = [];
$errors = [];

try {
    // Process each uploaded image
    $fileCount = count($files['name']);
    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = "File {$files['name'][$i]}: Upload error";
            continue;
        }

        $fileType = mime_content_type($files['tmp_name'][$i]);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "File {$files['name'][$i]}: Invalid file type";
            continue;
        }

        if ($files['size'][$i] > 5 * 1024 * 1024) {
            $errors[] = "File {$files['name'][$i]}: File too large (max 5MB)";
            continue;
        }

        $imageData = file_get_contents($files['tmp_name'][$i]);
        $description = isset($descriptions[$i]) ? trim($descriptions[$i]) : '';
        if (empty($description)) {
            $description = "Image for program";
        }

        // Insert into images table
        $stmt = $conn->prepare("INSERT INTO images (program_id, image_name, image_desc, description, image_type, image_size) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssi", $programId, $imageData, $description, $description, $fileType, $files['size'][$i]);
        
        if ($stmt->execute()) {
            $imageId = $conn->insert_id;
            $uploadedImages[] = [
                'image_id' => $imageId,
                'file_name' => $files['name'][$i],
                'description' => $description
            ];
        } else {
            $errors[] = "File {$files['name'][$i]}: Failed to save";
        }
        
        $stmt->close();
    }

    if (!empty($errors)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Some images failed to upload: ' . implode('; ', $errors),
            'uploaded' => $uploadedImages
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'message' => 'All images uploaded successfully',
            'uploaded' => $uploadedImages
        ]);
    }

} catch (Exception $e) {
    error_log('Upload program images error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to upload images: ' . $e->getMessage()]);
}

$conn->close();
?>