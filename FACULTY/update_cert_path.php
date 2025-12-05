<?php
require_once 'db.php';
$conn->query("UPDATE certificates SET certificate_file = 'certificates/certificate_4.pdf' WHERE id = 1");
$r = $conn->query('SELECT certificate_file FROM certificates WHERE id = 1');
$row = $r->fetch_assoc();
echo 'Certificate path updated to: ' . $row['certificate_file'] . PHP_EOL;
?>
