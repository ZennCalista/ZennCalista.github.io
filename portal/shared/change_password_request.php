<?php
// Start output buffering to catch any errors before JSON output
ob_start();

session_start();

// Set error handling to output JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Custom error handler to output JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $errstr]);
    exit;
});

header('Content-Type: application/json');

try {
    require_once '../../register/db.php';
    require_once '../../register/otp_utils.php';
    require_once 'password_utils.php';
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Server configuration error: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['current_password'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Current password is required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$current_password = $data['current_password'];

// Verify current password
if (!PasswordUtils::verifyCurrentPassword($user_id, $current_password, $conn)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit;
}

// Get user email from database
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($email);
if (!$stmt->fetch()) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}
$stmt->close();

if (empty($email)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Email not found for user']);
    exit;
}

// Initialize OTP utilities
try {
    $otp_utils = new OTPUtils($conn);
    $otp = $otp_utils->generateOTP();

    // Store OTP in database
    $store_result = $otp_utils->storeOTP($user_id, $email, $otp);
    if ($store_result['status'] === 'success') {
        // Send OTP via email
        $send_result = $otp_utils->sendOTP($email, $otp);
        if ($send_result['status'] === 'success') {
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'OTP sent to your email',
                'email' => $email,
                'expires_in' => 180 // 3 minutes in seconds
            ]);
        } else {
            ob_clean();
            echo json_encode(['success' => false, 'message' => $send_result['message'] ?? 'Failed to send OTP']);
        }
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => $store_result['message'] ?? 'Failed to store OTP']);
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error generating OTP: ' . $e->getMessage()]);
}
?>
