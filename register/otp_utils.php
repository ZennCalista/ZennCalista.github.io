<?php
require_once 'vendor/autoload.php';
require_once 'email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class OTPUtils {
    private $conn;
    private $config;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->config = require 'email_config.php';
    }

    /**
     * Generate a 6-digit OTP code
     */
    public function generateOTP() {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send OTP via email
     */
    public function sendOTP($email, $otp_code) {
        $mail = new PHPMailer(true);

        try {
            // Server settings from config
            $mail->isSMTP();
            $mail->Host = $this->config['smtp']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp']['username'];
            $mail->Password = $this->config['smtp']['password'];
            $mail->SMTPSecure = $this->config['smtp']['encryption'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $this->config['smtp']['port'];

            // Recipients
            $mail->setFrom($this->config['smtp']['from_email'], $this->config['smtp']['from_name']);
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your eTracker Verification Code';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <h2 style='color: #2d3748; text-align: center;'>eTracker Email Verification</h2>
                    <p style='color: #4a5568; font-size: 16px;'>Hello,</p>
                    <p style='color: #4a5568; font-size: 16px;'>Your verification code for eTracker registration is:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <span style='font-size: 32px; font-weight: bold; color: #2b6cb0; background: #edf2f7; padding: 15px 30px; border-radius: 8px; letter-spacing: 3px;'>{$otp_code}</span>
                    </div>
                    <p style='color: #4a5568; font-size: 16px;'>This code will expire in 10 minutes.</p>
                    <p style='color: #4a5568; font-size: 16px;'>If you didn't request this code, please ignore this email.</p>
                    <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 30px 0;'>
                    <p style='color: #718096; font-size: 14px; text-align: center;'>CVSU Imus - Extension Services</p>
                </div>
            ";
            $mail->AltBody = "Your eTracker verification code is: {$otp_code}. This code will expire in 10 minutes.";

            $mail->send();
            return ['status' => 'success', 'message' => 'OTP sent successfully'];
        } catch (Exception $e) {
            error_log("Email sending failed: " . $mail->ErrorInfo);
            return ['status' => 'error', 'message' => 'Failed to send OTP email'];
        }
    }

    /**
     * Store OTP in database
     */
    public function storeOTP($user_id, $email, $otp_code) {
        // Clean up expired OTPs first
        $this->cleanupExpiredOTPs();

        // Delete any existing OTP for this user
        $delete_stmt = $this->conn->prepare("DELETE FROM otp_verifications WHERE user_id = ?");
        $delete_stmt->bind_param("i", $user_id);
        $delete_stmt->execute();
        $delete_stmt->close();

        // Insert new OTP
        $expires_at = date('Y-m-d H:i:s', strtotime('+'.$this->config['otp']['expiry_minutes'].' minutes'));
        $stmt = $this->conn->prepare("INSERT INTO otp_verifications (user_id, email, otp_code, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $email, $otp_code, $expires_at);

        if ($stmt->execute()) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'OTP stored successfully'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Failed to store OTP'];
        }
    }

    /**
     * Verify OTP code
     */
    public function verifyOTP($user_id, $otp_code) {
        $stmt = $this->conn->prepare("
            SELECT id, used FROM otp_verifications
            WHERE user_id = ? AND otp_code = ? AND expires_at > NOW() AND used = FALSE
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->bind_param("is", $user_id, $otp_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $otp_id = $row['id'];

            // Mark OTP as used
            $update_stmt = $this->conn->prepare("UPDATE otp_verifications SET used = TRUE WHERE id = ?");
            $update_stmt->bind_param("i", $otp_id);
            $update_stmt->execute();
            $update_stmt->close();

            $stmt->close();
            return ['status' => 'success', 'message' => 'OTP verified successfully'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Invalid or expired OTP code'];
        }
    }

    /**
     * Check if user has a pending OTP
     */
    public function hasPendingOTP($user_id) {
        $stmt = $this->conn->prepare("
            SELECT id FROM otp_verifications
            WHERE user_id = ? AND expires_at > NOW() AND used = FALSE
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $has_pending = $result->num_rows > 0;
        $stmt->close();
        return $has_pending;
    }

    /**
     * Clean up expired OTPs
     */
    private function cleanupExpiredOTPs() {
        $this->conn->query("DELETE FROM otp_verifications WHERE expires_at <= NOW() OR used = TRUE");
    }

    /**
     * Get OTP expiration time for a user
     */
    public function getOTPExpiration($user_id) {
        $stmt = $this->conn->prepare("
            SELECT expires_at FROM otp_verifications
            WHERE user_id = ? AND used = FALSE
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return strtotime($row['expires_at']);
        } else {
            $stmt->close();
            return null;
        }
    }
}
?>