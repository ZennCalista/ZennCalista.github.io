<?php
// Test registration process
echo "<h2>Testing Registration Process</h2>";

// Test 1: Initial registration for non_acad user
echo "<h3>Test 1: Initial Non-Acad Registration</h3>";
$testData1 = json_encode([
    'firstname' => 'Test',
    'lastname' => 'NonAcad',
    'mi' => 'N',
    'email' => 'test_nonacad_' . time() . '@example.com',
    'password' => 'testpass123',
    'role' => 'non_acad'
]);

$ch = curl_init('http://localhost:8000/register/register.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $testData1);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response1 = curl_exec($ch);
$httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Request: " . $testData1 . "<br>";
echo "Response: " . $response1 . "<br>";
echo "HTTP Code: " . $httpCode1 . "<br><br>";

$data1 = json_decode($response1, true);
if ($data1 && $data1['status'] === 'success') {
    $userId = $data1['user_id'];
    echo "<h3>Test 2: Role-Specific Non-Acad Registration</h3>";

    // Test 2: Role-specific registration
    $testData2 = json_encode([
        'user_id' => $userId,
        'role' => 'non_acad',
        'contact_no' => '09123456789',
        'emergency_contact' => 'John Doe - 09123456788'
    ]);

    $ch = curl_init('http://localhost:8000/register/register.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $testData2);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response2 = curl_exec($ch);
    $httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Request: " . $testData2 . "<br>";
    echo "Response: " . $response2 . "<br>";
    echo "HTTP Code: " . $httpCode2 . "<br><br>";

    $data2 = json_decode($response2, true);
    if ($data2 && $data2['status'] === 'success') {
        echo "<span style='color: green;'>✅ Registration test PASSED</span><br>";
    } else {
        echo "<span style='color: red;'>❌ Registration test FAILED</span><br>";
        echo "Error: " . ($data2['message'] ?? 'Unknown error') . "<br>";
    }
} else {
    echo "<span style='color: red;'>❌ Initial registration test FAILED</span><br>";
    echo "Error: " . ($data1['message'] ?? 'Unknown error') . "<br>";
}
?>