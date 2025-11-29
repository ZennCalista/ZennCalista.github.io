<?php
require_once 'ADMIN/db.php';

$result = $conn->query('SELECT f.id, f.user_id, u.firstname, u.lastname FROM faculty f JOIN users u ON f.user_id = u.id');
echo "Faculty to User mapping:\n";
while ($row = $result->fetch_assoc()) {
    echo 'Faculty ID: ' . $row['id'] . ' | User ID: ' . $row['user_id'] . ' | Name: ' . $row['firstname'] . ' ' . $row['lastname'] . "\n";
}
?>