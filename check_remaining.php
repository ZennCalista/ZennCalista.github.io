<?php
require_once 'ADMIN/db.php';

$result = $conn->query('SELECT message FROM notifications WHERE audience="admin" AND is_active=1');
while ($row = $result->fetch_assoc()) {
    echo $row['message'] . PHP_EOL;
}
?>