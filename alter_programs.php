<?php
include 'backend/db.php';

$conn->query("ALTER TABLE programs MODIFY department_id INT NULL");

echo "Programs table altered\n";
?>