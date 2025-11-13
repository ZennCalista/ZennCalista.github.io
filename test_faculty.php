<?php
include 'ADMIN/db.php';

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'faculty'");
$row = $result->fetch_assoc();
echo "Faculty users in users table: " . $row['count'] . "\n";

$result = $conn->query("SELECT COUNT(*) as count FROM faculty");
$row = $result->fetch_assoc();
echo "Entries in faculty table: " . $row['count'] . "\n";

$result = $conn->query("SELECT u.id, u.firstname, u.lastname, u.role, f.faculty_id FROM users u LEFT JOIN faculty f ON u.id = f.user_id WHERE u.role = 'faculty'");
echo "Faculty users with join:\n";
while ($row = $result->fetch_assoc()) {
    echo $row['id'] . ' ' . $row['firstname'] . ' ' . $row['lastname'] . ' ' . $row['role'] . ' ' . ($row['faculty_id'] ?: 'NULL') . "\n";
}
?>