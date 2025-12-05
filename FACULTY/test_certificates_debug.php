<?php
require_once 'db.php';

echo "=== CERTIFICATES TABLE CHECK ===" . PHP_EOL;

// Count total certificates
$result = $conn->query('SELECT COUNT(*) as cnt FROM certificates');
$row = $result->fetch_assoc();
echo "Total certificates: " . $row['cnt'] . PHP_EOL . PHP_EOL;

// Check certificates with program info
echo "=== CERTIFICATES WITH PROGRAM INFO ===" . PHP_EOL;
$result = $conn->query("SELECT c.id, c.student_name, c.program_id, c.certificate_file, p.program_name, p.faculty_id 
                         FROM certificates c 
                         LEFT JOIN programs p ON c.program_id = p.id 
                         LIMIT 5");
while($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . ", Student: " . $row['student_name'] . 
         ", Program ID: " . $row['program_id'] . ", Program: " . $row['program_name'] . 
         ", Faculty ID: " . $row['faculty_id'] . ", File: " . $row['certificate_file'] . PHP_EOL;
}

// Check faculty IDs
echo PHP_EOL . "=== FACULTY IDs ===" . PHP_EOL;
$result = $conn->query("SELECT f.id, f.user_id, u.firstname, u.lastname FROM faculty f JOIN users u ON f.user_id = u.id LIMIT 5");
while($row = $result->fetch_assoc()) {
    echo "Faculty ID: " . $row['id'] . ", User ID: " . $row['user_id'] . 
         ", Name: " . $row['firstname'] . " " . $row['lastname'] . PHP_EOL;
}
?>
