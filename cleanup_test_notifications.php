<?php
require_once 'ADMIN/db.php';

$conn->query('DELETE FROM notifications WHERE message LIKE "Test%" OR message LIKE "New proposal submitted: \"Test%" OR message LIKE "New document uploaded: progress_report%"');
echo 'Test notifications cleaned up.\n';

$result = $conn->query('SELECT COUNT(*) as count FROM notifications WHERE audience="admin" AND is_active=1');
$row = $result->fetch_assoc();
echo 'Remaining active admin notifications: ' . $row['count'] . PHP_EOL;
?>