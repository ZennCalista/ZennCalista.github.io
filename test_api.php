<?php
// Test the admin notifications API
$ch = curl_init('http://localhost/Etracker/ADMIN/api_notifications.php?for=admin');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo "Admin API Response:\n";
echo $response . "\n\n";

// Test faculty API
$ch = curl_init('http://localhost/Etracker/ADMIN/api_notifications.php?for=faculty');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo "Faculty API Response:\n";
echo $response . "\n";
?>