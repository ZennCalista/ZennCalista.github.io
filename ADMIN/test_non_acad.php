<?php
// Test Non-Acad user functionality
require_once 'db.php';

echo "<h2>Non-Acad User Type Test</h2>";

try {
    // Check users table structure
    echo "<h3>Users Table Structure:</h3>";
    $result = $conn->query("DESCRIBE users");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";

    // Check if non_acad is in the role ENUM
    $result = $conn->query("DESCRIBE users");
    $roleType = '';
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] === 'role') {
            $roleType = $row['Type'];
            break;
        }
    }

    if (strpos($roleType, 'non_acad') !== false) {
        echo "✅ 'non_acad' is included in the role ENUM<br>";
    } else {
        echo "❌ 'non_acad' is NOT found in the role ENUM<br>";
        echo "Role type: " . htmlspecialchars($roleType) . "<br>";
    }

    // Test inserting a non_acad user
    echo "<h3>Testing Non-Acad User Creation:</h3>";
    $testEmail = 'test_non_acad_' . time() . '@example.com';
    $insertQuery = "INSERT INTO users (firstname, lastname, email, password, role, verification_status, created_at)
                   VALUES ('Test', 'NonAcad', ?, ?, 'non_acad', 'verified', NOW())";

    $stmt = $conn->prepare($insertQuery);
    $hashedPassword = password_hash('testpass123', PASSWORD_DEFAULT);
    $stmt->bind_param('ss', $testEmail, $hashedPassword);

    if ($stmt->execute()) {
        $newUserId = $conn->insert_id;
        echo "✅ Successfully created test non_acad user with ID: $newUserId<br>";

        // Test fetching the user
        $selectQuery = "SELECT id, firstname, lastname, email, role, verification_status FROM users WHERE id = ?";
        $stmt = $conn->prepare($selectQuery);
        $stmt->bind_param('i', $newUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && $user['role'] === 'non_acad') {
            echo "✅ Successfully retrieved non_acad user: " . htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) . "<br>";
        } else {
            echo "❌ Failed to retrieve non_acad user or role mismatch<br>";
        }

        // Clean up test user
        $conn->query("DELETE FROM users WHERE id = $newUserId");
        echo "✅ Test user cleaned up<br>";
    } else {
        echo "❌ Failed to create test non_acad user: " . $stmt->error . "<br>";
    }

    // Check API endpoint
    echo "<h3>API Test:</h3>";
    echo "<a href='api_users.php?role=non_acad' target='_blank'>🔗 Test Non-Acad API Endpoint</a><br>";
    echo "<a href='User.html' target='_blank'>🔗 Open Admin User Management</a><br>";

} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>