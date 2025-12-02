<?php
session_start();

// Set error handling to output JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    require_once '../../register/db.php';
    require_once '../../register/otp_utils.php';
    require_once 'password_utils.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server configuration error: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['otp']) || !isset($data['new_password']) || !isset($data['confirm_password'])) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user email from database
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($email);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}
$stmt->close();
$otp = $data['otp'];
$new_password = $data['new_password'];
$confirm_password = $data['confirm_password'];

// Check if passwords match
if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

// Validate password requirements
$validation = PasswordUtils::validatePassword($new_password);
if (!$validation['valid']) {
    echo json_encode([
        'success' => false,
        'message' => 'Password does not meet requirements',
        'errors' => $validation['errors']
    ]);
    exit;
}

// Check if new password is same as current password
if (PasswordUtils::isSameAsCurrentPassword($user_id, $new_password, $conn)) {
    echo json_encode(['success' => false, 'message' => 'New password cannot be the same as current password']);
    exit;
}

// Initialize OTP utilities and verify OTP
$otp_utils = new OTPUtils($conn);
$verify_result = $otp_utils->verifyOTP($user_id, $otp);
if ($verify_result['status'] !== 'success') {
    echo json_encode(['success' => false, 'message' => $verify_result['message'] ?? 'Invalid or expired OTP']);
    exit;
}

// Update password
if (PasswordUtils::updatePassword($user_id, $new_password, $conn)) {
    // Log the password change (optional)
    PasswordUtils::logPasswordChange($user_id, $conn);
    
    // Delete used OTP
    $stmt = $conn->prepare("DELETE FROM otp_verifications WHERE user_id = ? AND email = ?");
    if ($stmt) {
        $stmt->bind_param("is", $user_id, $email);
        $stmt->execute();
        $stmt->close();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Password changed successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update password. Please try again.']);
}
?>
