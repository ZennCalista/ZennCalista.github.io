<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

include 'db.php';
include 'otp_utils.php';

$otp_utils = new OTPUtils($conn);
$config = require 'email_config.php';

// Get the raw POST data
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE || !$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON input"]);
    $conn->close();
    exit;
}

// Validate required fields
if (!isset($data['user_id']) || !isset($data['email'])) {
    echo json_encode(["status" => "error", "message" => "User ID and email are required"]);
    $conn->close();
    exit;
}

$user_id = intval($data['user_id']);
$email = trim($data['email']);

// Validate email format and domain
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@cvsu\.edu\.ph$/', $email)) {
    echo json_encode(["status" => "error", "message" => "Only @cvsu.edu.ph email addresses are allowed"]);
    $conn->close();
    exit;
}

// Check if user exists and email matches
$user_check = $conn->prepare("SELECT id FROM users WHERE id = ? AND email = ?");
$user_check->bind_param("is", $user_id, $email);
$user_check->execute();
$user_result = $user_check->get_result();

if ($user_result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "User not found or email mismatch"]);
    $user_check->close();
    $conn->close();
    exit;
}
$user_check->close();

// Check rate limiting (max 3 OTP requests per hour per user)
$hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
$rate_check = $conn->prepare("SELECT COUNT(*) as request_count FROM otp_verifications WHERE user_id = ? AND created_at > ?");
$rate_check->bind_param("is", $user_id, $hour_ago);
$rate_check->execute();
$rate_result = $rate_check->get_result();
$rate_row = $rate_result->fetch_assoc();

if ($rate_row['request_count'] >= $config['otp']['max_attempts_per_hour']) {
    echo json_encode(["status" => "error", "message" => "Too many OTP requests. Please try again in an hour."]);
    $rate_check->close();
    $conn->close();
    exit;
}
$rate_check->close();

// Generate and send OTP
$otp_code = $otp_utils->generateOTP();

// Store OTP in database
$store_result = $otp_utils->storeOTP($user_id, $email, $otp_code);
if ($store_result['status'] !== 'success') {
    echo json_encode(["status" => "error", "message" => $store_result['message']]);
    $conn->close();
    exit;
}

// Send OTP via email
$email_result = $otp_utils->sendOTP($email, $otp_code);
if ($email_result['status'] !== 'success') {
    echo json_encode(["status" => "error", "message" => $email_result['message']]);
    $conn->close();
    exit;
}

// Success response (don't include OTP in response for security)
$message = "OTP sent successfully to your email";
if (strpos($email_result['message'], 'logged for testing') !== false) {
    $message = "TESTING MODE: Check your PHP error log for the OTP code";
}

echo json_encode([
    "status" => "success",
    "message" => $message,
    "expires_in" => $config['otp']['expiry_minutes'] * 60 // Convert to seconds
]);

$conn->close();
?>