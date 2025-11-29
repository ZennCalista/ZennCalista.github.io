<?php
require_once 'ADMIN/db.php';

echo "Testing notification system...\n\n";

// Check current admin notifications
$result = $conn->query('SELECT COUNT(*) as count FROM notifications WHERE audience="admin" AND is_active=1');
$row = $result->fetch_assoc();
echo 'Active admin notifications: ' . $row['count'] . "\n\n";

$result = $conn->query('SELECT message, priority, created_at FROM notifications WHERE audience="admin" AND is_active=1 ORDER BY created_at DESC LIMIT 5');
echo "Recent admin notifications:\n";
while ($row = $result->fetch_assoc()) {
    echo '- ' . $row['message'] . ' | Priority: ' . $row['priority'] . ' | Created: ' . $row['created_at'] . "\n";
}

echo "\nTesting notification creation...\n";

// Create a test notification
$test_message = "Test notification: Faculty uploaded a document";
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