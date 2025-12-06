<?php
// Script to create a test super admin user
require_once 'db.php';

echo "Creating test super admin user...\n\n";

try {
    // Check if super admin already exists
    $check = $conn->query("SELECT id FROM users WHERE email = 'superadmin@etracker.com'");
    if ($check && $check->num_rows > 0) {
        echo " Super admin user already exists (superadmin@etracker.com)\n";
        exit(0);
    }

    // Create super admin user
    $email = 'superadmin@etracker.com';
    $password = password_hash('SuperAdmin123!', PASSWORD_DEFAULT);
    $firstname = 'Super';
    $lastname = 'Admin';
    
    $sql = "INSERT INTO users (firstname, lastname, email, password, role, verification_status) 
            VALUES (?, ?, ?, ?, 'super_admin', 'verified')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $firstname, $lastname, $email, $password);
    
    if ($stmt->execute()) {
        echo " Successfully created super admin user!\n\n";
        echo "Login Credentials:\n";
        echo "Email: superadmin@etracker.com\n";
        echo "Password: SuperAdmin123!\n\n";
        echo "You can now log in with these credentials.\n";
    } else {
        throw new Exception("Failed to create super admin user");
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo " Error: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
?>
