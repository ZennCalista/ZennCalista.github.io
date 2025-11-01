<?php
// Backup copy of test_upload.php moved to .trash on 2025-10-26
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Upload Debug Test</h2>";

// Test 1: Check if POST data is received
echo "<h3>1. POST Data Received:</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "✅ POST request received<br>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
} else {
    echo "❌ No POST request (Method: " . $_SERVER['REQUEST_METHOD'] . ")<br>";
}

// Test 2: Check file uploads
echo "<h3>2. File Upload Data:</h3>";
if (isset($_FILES['images'])) {
    echo "✅ Files received<br>";
    echo "<pre>";
    print_r($_FILES['images']);
    echo "</pre>";
} else {
    echo "❌ No files received<br>";
}

// Test 3: Check database connection
echo "<h3>3. Database Connection:</h3>";
try {
    include '../db.php';
    if ($conn->connect_error) {
        echo "❌ Database connection failed: " . $conn->connect_error . "<br>";
    } else {
        echo "✅ Database connected successfully<br>";
        
        // Test if programs table exists
        $result = $conn->query("SHOW TABLES LIKE 'programs'");
        if ($result->num_rows > 0) {
            echo "✅ Programs table exists<br>";
        } else {
            echo "❌ Programs table does not exist<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 4: Check required fields
echo "<h3>4. Required Fields Check:</h3>";
$required_fields = ['program_name', 'department', 'location', 'status', 'description'];
foreach ($required_fields as $field) {
    if (isset($_POST[$field]) && !empty($_POST[$field])) {
        echo "✅ $field: " . htmlspecialchars($_POST[$field]) . "<br>";
    } else {
        echo "❌ $field: missing or empty<br>";
    }
}

// Test 5: PHP Configuration
echo "<h3>5. PHP Configuration:</h3>";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "Post Max Size: " . ini_get('post_max_size') . "<br>";
echo "Max File Uploads: " . ini_get('max_file_uploads') . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";

?>
