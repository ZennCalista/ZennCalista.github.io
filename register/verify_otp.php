<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

include 'db.php';
include 'otp_utils.php';

$otp_utils = new OTPUtils($conn);

// Get the raw POST data
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE || !$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON input"]);
    $conn->close();
    exit;
}

// Validate required fields
if (!isset($data['user_id']) || !isset($data['otp_code'])) {
    echo json_encode(["status" => "error", "message" => "User ID and OTP code are required"]);
    $conn->close();
    exit;
}

$user_id = intval($data['user_id']);
$otp_code = trim($data['otp_code']);

// Validate OTP format (should be 6 digits)
if (!preg_match('/^\d{6}$/', $otp_code)) {
    echo json_encode(["status" => "error", "message" => "Invalid OTP format. Please enter a 6-digit code."]);
    $conn->close();
    exit;
}

// Verify OTP
$verify_result = $otp_utils->verifyOTP($user_id, $otp_code);

if ($verify_result['status'] === 'success') {
    // Mark user email as verified
    $update_stmt = $conn->prepare("UPDATE users SET email_verified = TRUE WHERE id = ?");
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();
    $update_stmt->close();

    echo json_encode([
        "status" => "success",
        "message" => "Email verified successfully! You can now complete your registration."
    ]);
} else {
    echo json_encode(["status" => "error", "message" => $verify_result['message']]);
}

$conn->close();
?>