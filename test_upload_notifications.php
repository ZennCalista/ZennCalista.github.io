<?php
require_once 'ADMIN/db.php';

echo "Testing upload notification creation...\n\n";

// Simulate proposal notification creation (like in upload_handler.php)
$proposal_title = "Test Proposal for SDG Goals";
$notification_message = "New proposal submitted: \"$proposal_title\" by faculty member. Awaiting approval.";
$notification_sql = "INSERT INTO notifications (message, priority, audience, is_active, created_at) VALUES (?, 'medium', 'admin', 1, NOW())";
$notification_stmt = $conn->prepare($notification_sql);
$notification_stmt->bind_param('s', $notification_message);
$notification_stmt->execute();
$notification_stmt->close();

echo "Proposal notification created: $notification_message\n";

// Simulate document upload notification creation
$document_type = "progress_report";
$program_name = "Community Health Program";
$notification_message = "New document uploaded: $document_type for program \"$program_name\" by faculty member.";
$notification_sql = "INSERT INTO notifications (message, priority, audience, is_active, created_at) VALUES (?, 'low', 'admin', 1, NOW())";
$notification_stmt = $conn->prepare($notification_sql);
$notification_stmt->bind_param('s', $notification_message);
$notification_stmt->execute();
$notification_stmt->close();

echo "Document notification created: $notification_message\n";

// Check final count
$result = $conn->query('SELECT COUNT(*) as count FROM notifications WHERE audience="admin" AND is_active=1');
$row = $result->fetch_assoc();
echo "\nTotal active admin notifications: " . $row['count'] . "\n";

$result = $conn->query('SELECT message, priority FROM notifications WHERE audience="admin" AND is_active=1 ORDER BY created_at DESC LIMIT 2');
echo "\nLatest notifications:\n";
while ($row = $result->fetch_assoc()) {
    echo '- ' . $row['message'] . ' | Priority: ' . $row['priority'] . "\n";
}

echo "\nUpload notification test completed!\n";
?>