<?php
require_once 'db.php';
$result = $conn->query('SELECT c.id, c.student_name, c.certificate_file, c.program_id, p.program_name FROM certificates c LEFT JOIN programs p ON c.program_id = p.id');
while($row = $result->fetch_assoc()) {
    echo 'ID: ' . $row['id'] . ' | Student: ' . $row['student_name'] . ' | File: ' . $row['certificate_file'] . ' | Program: ' . $row['program_name'] . PHP_EOL;
}

