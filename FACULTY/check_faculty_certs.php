<?php
require_once 'db.php';
echo 'Programs with faculty certificates for Faculty ID 2:' . PHP_EOL;
$result = $conn->query('SELECT id, program_name, faculty_certificate_issued, faculty_certificate_issued_on, faculty_certificate_file FROM programs WHERE faculty_id = 2 AND faculty_certificate_file IS NOT NULL ORDER BY id');
while($row = $result->fetch_assoc()) {
    echo 'Program ID: ' . $row['id'] . ' | Name: ' . $row['program_name'] . ' | Issued: ' . ($row['faculty_certificate_issued'] ? 'Yes' : 'No') . ' | File: ' . $row['faculty_certificate_file'] . PHP_EOL;
}

