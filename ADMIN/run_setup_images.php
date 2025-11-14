<?php
include '../backend/db.php';

$sql = file_get_contents('setup_program_images.sql');

if ($conn->query($sql) === TRUE) {
    echo "Program images table created successfully";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>