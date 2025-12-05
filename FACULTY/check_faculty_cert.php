<?php
require_once 'db.php';
echo 'Faculty records:' . PHP_EOL;
$faculty = $conn->query('SELECT f.id, f.user_id, u.firstname, u.lastname FROM faculty f JOIN users u ON f.user_id = u.id');
while($row = $faculty->fetch_assoc()) {
    echo 'Faculty ID: ' . $row['id'] . ' | User ID: ' . $row['user_id'] . ' | Name: ' . $row['firstname'] . ' ' . $row['lastname'] . PHP_EOL;
}
echo PHP_EOL . 'All certificate records:' . PHP_EOL;
$certs = $conn->query('SELECT c.id, c.student_name, c.certificate_file, c.program_id, c.faculty_user_id, p.program_name, p.faculty_id FROM certificates c LEFT JOIN programs p ON c.program_id = p.id');
while($row = $certs->fetch_assoc()) {
    echo 'Cert ID: ' . $row['id'] . ' | Student: ' . $row['student_name'] . ' | Faculty User ID: ' . ($row['faculty_user_id'] ?? 'NULL') . ' | Program Faculty ID: ' . ($row['faculty_id'] ?? 'NULL') . ' | File: ' . $row['certificate_file'] . PHP_EOL;
}

