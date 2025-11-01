<?php
session_start();
require_once '../FACULTY/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Email and password are required']);
        exit;
    }

    // Authentication is handled against the users table in the database (no hard-coded fallbacks)

    // Fetch full user record so we can populate session['user'] for portal compatibility
    $stmt = $conn->prepare("SELECT id, password, role, firstname, lastname, email FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        error_log('Prepare failed in login.php: ' . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Server error']);
        exit;
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Support legacy plaintext passwords by detecting non-password_hash values
    $ok = false;
    $upgrade = false;
    if ($user) {
        if (password_get_info($user['password'])['algo']) {
            $ok = password_verify($password, $user['password']);
        } else {
            // legacy/plaintext stored password
            $ok = hash_equals($user['password'], $password);
            $upgrade = $ok;
        }
    }

    if (!$ok) {
        error_log("Login failed for email: $email");
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
        exit;
    }

    // If we matched a legacy password, upgrade to bcrypt
    if ($upgrade) {
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        $up = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        if ($up) {
            $up->bind_param('si', $newHash, $user['id']);
            $up->execute();
            $up->close();
        } else {
            error_log('Failed to prepare password upgrade: ' . $conn->error);
        }
    }

    // Successful login: create session values compatible with both systems
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'firstname' => $user['firstname'] ?? '',
        'lastname' => $user['lastname'] ?? '',
        'email' => $user['email'] ?? $email,
        'role' => $user['role']
    ];

    error_log("Login successful: user_id={$user['id']}, role={$user['role']}");
    // Route based on role: faculty -> faculty dashboard, admin -> admin dashboard, otherwise student
    if ($user['role'] === 'faculty') {
        $redirect_url = '../FACULTY/Dashboard.php';
    } elseif ($user['role'] === 'admin') {
        $redirect_url = '../ADMIN/Dashboard.html';
    } else {
        $redirect_url = '../STUDENT/index.php';
    }
    echo json_encode(['status' => 'success', 'redirect_url' => $redirect_url]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

?>