<?php
require_once 'ADMIN/db.php';

echo "Checking admin notifications:\n";
$result = $conn->query("SELECT id, message, audience, is_active, expires_at FROM notifications WHERE audience IN ('admin', 'all') ORDER BY created_at DESC");
$notifications = $result->fetch_all(MYSQLI_ASSOC);
echo 'Admin notifications: ' . count($notifications) . "\n";
foreach ($notifications as $notif) {
    echo "- ID: {$notif['id']}, Message: {$notif['message']}, Audience: {$notif['audience']}, Active: {$notif['is_active']}, Expires: {$notif['expires_at']}\n";
}

echo "\nChecking all notifications:\n";
$result = $conn->query("SELECT id, message, audience, is_active, expires_at FROM notifications ORDER BY created_at DESC");
$all_notifications = $result->fetch_all(MYSQLI_ASSOC);
echo 'All notifications: ' . count($all_notifications) . "\n";
foreach ($all_notifications as $notif) {
    echo "- ID: {$notif['id']}, Message: {$notif['message']}, Audience: {$notif['audience']}, Active: {$notif['is_active']}, Expires: {$notif['expires_at']}\n";
}
?>