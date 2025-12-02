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

if (!isset($data['current_password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$current_password = $data['current_password'];

// Verify current password
if (!PasswordUtils::verifyCurrentPassword($user_id, $current_password, $conn)) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit;
}

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

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email not found for user']);
    exit;
}

// Initialize OTP utilities
$otp_utils = new OTPUtils($conn);
$otp = $otp_utils->generateOTP();

// Store OTP in database
$store_result = $otp_utils->storeOTP($user_id, $email, $otp);
if ($store_result['status'] === 'success') {
    // Send OTP via email
    $send_result = $otp_utils->sendOTP($email, $otp);
    if ($send_result['status'] === 'success') {
        echo json_encode([
            'success' => true,
            'message' => 'OTP sent to your email',
            'email' => $email,
            'expires_in' => 600 // 10 minutes in seconds
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $send_result['message'] ?? 'Failed to send OTP']);
    }
} else {
    echo json_encode(['success' => false, 'message' => $store_result['message'] ?? 'Failed to store OTP']);
}
?>
