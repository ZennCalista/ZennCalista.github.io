<?php
// Test student registration
include 'db.php';

echo "<h2>Student Registration Test</h2>";

// Test data
$testData = [
    'firstname' => 'Test',
    'lastname' => 'Student',
    'mi' => 'S',
    'email' => 'test_student_' . time() . '@example.com',
    'password' => 'testpass123',
    'role' => 'student'
];

echo "<h3>Step 1: Initial Registration</h3>";
$firstname = $testData['firstname'];
$lastname = $testData['lastname'];
$email = $testData['email'];
$password = password_hash($testData['password'], PASSWORD_DEFAULT);
$role = $testData['role'];

$sql = "INSERT INTO users (firstname, lastname, email, password, role) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $firstname, $lastname, $email, $password, $role);

if ($stmt->execute()) {
    $userId = $conn->insert_id;
    echo "✅ Initial registration successful, User ID: $userId<br><br>";

    echo "<h3>Step 2: Role-Specific Registration</h3>";
    $roleData = [
        'user_id' => $userId,
        'role' => 'student',
        'contact_no' => '09123456789',
        'emergency_contact' => 'Jane Doe - 09123456788'
    ];

    $contact_no = $roleData['contact_no'];
    $emergency_contact = $roleData['emergency_contact'];
    $student_id = null; // Not provided (removed from frontend)
    $course = null; // Not provided (removed from frontend)

    $sql2 = "INSERT INTO students (user_id, student_id, course, contact_no, emergency_contact) VALUES (?, ?, ?, ?, ?)";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("issss", $userId, $student_id, $course, $contact_no, $emergency_contact);

    if ($stmt2->execute()) {
        echo "✅ Role-specific registration successful<br>";
        echo "<span style='color: green;'>🎉 Student registration test PASSED</span><br>";
    } else {
        echo "❌ Role-specific registration failed: " . $stmt2->error . "<br>";
    }
    $stmt2->close();

} else {
    echo "❌ Initial registration failed: " . $stmt->error . "<br>";
}

$stmt->close();
$conn->close();
?>