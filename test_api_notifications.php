<?php
require_once 'ADMIN/db.php';

echo "Testing admin notifications API logic...\n\n";

// Simulate the admin GET request logic
$result = $conn->query("SELECT * FROM notifications WHERE audience IN ('admin', 'all') AND is_active=1 ORDER BY created_at DESC");
$notifications = $result->fetch_all(MYSQLI_ASSOC);

echo "API call successful!\n";
echo "Notifications count: " . count($notifications) . "\n\n";

foreach ($notifications as $notification) {
    echo "ID: " . $notification['id'] . "\n";
    echo "Message: " . $notification['message'] . "\n";
    echo "Priority: " . $notification['priority'] . "\n";
    echo "Audience: " . $notification['audience'] . "\n";
    echo "Created: " . $notification['created_at'] . "\n";
    echo "---\n";
}

echo "\nAPI logic test completed!\n";
?>