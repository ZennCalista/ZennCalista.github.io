<?php
include 'ADMIN/db.php';

echo "Total active notifications: ";
$result = $conn->query('SELECT COUNT(*) as total FROM notifications WHERE is_active=1');
$row = $result->fetch_assoc();
echo $row['total'] . "\n\n";

echo "Notifications by audience:\n";
$result = $conn->query('SELECT audience, COUNT(*) as count FROM notifications WHERE is_active=1 GROUP BY audience');
while($row = $result->fetch_assoc()) {
    echo $row['audience'] . ': ' . $row['count'] . "\n";
}

echo "\nSample notifications:\n";
$result = $conn->query('SELECT id, message, audience, priority FROM notifications WHERE is_active=1 ORDER BY created_at DESC LIMIT 5');
while($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, Audience: {$row['audience']}, Priority: {$row['priority']}\n";
    echo "Message: {$row['message']}\n\n";
}
?>
$notification_sql = "INSERT INTO notifications (message, priority, audience, is_active, created_at) VALUES (?, 'low', 'admin', 1, NOW())";
$notification_stmt = $conn->prepare($notification_sql);
$notification_stmt->bind_param('s', $test_message);
$notification_stmt->execute();
$notification_stmt->close();

echo "Test notification created successfully!\n";

// Check again
$result = $conn->query('SELECT COUNT(*) as count FROM notifications WHERE audience="admin" AND is_active=1');
$row = $result->fetch_assoc();
echo 'Active admin notifications after test: ' . $row['count'] . "\n";

echo "\nTest completed!\n";
?>