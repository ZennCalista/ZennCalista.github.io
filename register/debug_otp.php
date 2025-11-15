<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

try {
    echo "Step 1: Starting OTP debug\n";

    include 'db.php';
    echo "Step 2: Database included\n";

    include 'otp_utils.php';
    echo "Step 3: OTP utils included\n";

    $otp_utils = new OTPUtils($conn);
    echo "Step 4: OTP utils instantiated\n";

    $config = require 'email_config.php';
    echo "Step 5: Config loaded\n";

    // Get the raw POST data
    $raw = file_get_contents("php://input");
    echo "Step 6: Raw input received: " . substr($raw, 0, 100) . "\n";

    $data = json_decode($raw, true);
    echo "Step 7: JSON decoded\n";

    if (json_last_error() !== JSON_ERROR_NONE || !$data) {
        echo json_encode(["status" => "error", "message" => "Invalid JSON input", "json_error" => json_last_error_msg()]);
        exit;
    }

    echo "Step 8: JSON validation passed\n";

    // Validate required fields
    if (!isset($data['user_id']) || !isset($data['email'])) {
        echo json_encode(["status" => "error", "message" => "User ID and email are required"]);
        exit;
    }

    $user_id = intval($data['user_id']);
    $email = trim($data['email']);

    echo "Step 9: User ID: $user_id, Email: $email\n";

    // Validate email format and domain
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@cvsu\.edu\.ph$/', $email)) {
        echo json_encode(["status" => "error", "message" => "Only @cvsu.edu.ph email addresses are allowed"]);
        exit;
    }

    echo "Step 10: Email validation passed\n";

    // Check if user exists and email matches
    $user_check = $conn->prepare("SELECT id FROM users WHERE id = ? AND email = ?");
    if (!$user_check) {
        echo json_encode(["status" => "error", "message" => "Database prepare failed: " . $conn->error]);
        exit;
    }

    $user_check->bind_param("is", $user_id, $email);
    if (!$user_check->execute()) {
        echo json_encode(["status" => "error", "message" => "Database execute failed: " . $user_check->error]);
        exit;
    }

    $user_result = $user_check->get_result();
    echo "Step 11: User check query executed, rows: " . $user_result->num_rows . "\n";

    if ($user_result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "User not found or email mismatch"]);
        $user_check->close();
        exit;
    }

    $user_check->close();
    echo "Step 12: User validation passed\n";

    // Check rate limiting
    $hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
    $rate_check = $conn->prepare("SELECT COUNT(*) as request_count FROM otp_verifications WHERE user_id = ? AND created_at > ?");
    $rate_check->bind_param("is", $user_id, $hour_ago);
    $rate_check->execute();
    $rate_result = $rate_check->get_result();
    $rate_row = $rate_result->fetch_assoc();

    if ($rate_row['request_count'] >= $config['otp']['max_attempts_per_hour']) {
        echo json_encode(["status" => "error", "message" => "Too many OTP requests. Please try again in an hour."]);
        $rate_check->close();
        exit;
    }
    $rate_check->close();

    echo "Step 13: Rate limiting passed\n";

    // Generate and send OTP
    $otp_code = $otp_utils->generateOTP();
    echo "Step 14: OTP generated: $otp_code\n";

    // Store OTP in database
    $store_result = $otp_utils->storeOTP($user_id, $email, $otp_code);
    if ($store_result['status'] !== 'success') {
        echo json_encode(["status" => "error", "message" => $store_result['message']]);
        exit;
    }

    echo "Step 15: OTP stored in database\n";

    // Send OTP via email
    $email_result = $otp_utils->sendOTP($email, $otp_code);
    if ($email_result['status'] !== 'success') {
        echo json_encode(["status" => "error", "message" => $email_result['message']]);
        exit;
    }

    echo "Step 16: OTP email sent\n";

    // Success response
    $message = "OTP sent successfully to your email";
    if (strpos($email_result['message'], 'logged for testing') !== false) {
        $message = "TESTING MODE: Check your PHP error log for the OTP code";
    }

    echo json_encode([
        "status" => "success",
        "message" => $message,
        "expires_in" => $config['otp']['expiry_minutes'] * 60
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Exception: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>