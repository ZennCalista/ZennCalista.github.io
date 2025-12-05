<?php
require_once 'db.php';
echo 'Faculty: Mhel Defensor - Faculty ID: 2, User ID: 18' . PHP_EOL . PHP_EOL;
echo 'Programs for this faculty:' . PHP_EOL;
$programs = $conn->query('SELECT id, program_name FROM programs WHERE faculty_id = 2');
while($row = $programs->fetch_assoc()) {
    echo 'Program ID: ' . $row['id'] . ' | Name: ' . $row['program_name'] . PHP_EOL;
}
echo PHP_EOL . 'All certificates:' . PHP_EOL;
$certs = $conn->query('SELECT c.id, c.student_name, c.certificate_file, c.program_id, p.program_name, p.faculty_id FROM certificates c LEFT JOIN programs p ON c.program_id = p.id ORDER BY c.id');
while($row = $certs->fetch_assoc()) {
    echo 'ID: ' . $row['id'] . ' | Student: ' . $row['student_name'] . ' | Program: ' . $row['program_name'] . ' | Faculty ID: ' . ($row['faculty_id'] ?? 'NULL') . ' | File: ' . $row['certificate_file'] . PHP_EOL;
}

