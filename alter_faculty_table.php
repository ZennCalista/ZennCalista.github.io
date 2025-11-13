<?php
include 'ADMIN/db.php';

$conn->query("ALTER TABLE faculty ADD COLUMN faculty_name VARCHAR(100) NULL");
$conn->query("ALTER TABLE faculty ADD COLUMN faculty_id VARCHAR(50) NULL");

echo "Faculty table altered successfully\n";
?>