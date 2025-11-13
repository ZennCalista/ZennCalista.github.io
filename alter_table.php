<?php
include 'register/db.php';

$result = $conn->query('ALTER TABLE users MODIFY department_id INT NULL');
if ($result) {
    echo "Altered users table successfully\n";
} else {
    echo "Error altering table: " . $conn->error . "\n";
}
?>