<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

try {
    // Load environment variables
    require_once __DIR__ . '/../env_loader.php';

    // Load config
    $config = require 'email_config.php';

    // Load PHPMailer
    require_once __DIR__ . '/../vendor/autoload.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    // Configure SMTP
    $mail->isSMTP();
    $mail->Host = $config['smtp']['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp']['username'];
    $mail->Password = $config['smtp']['password'];
    $mail->SMTPSecure = $config['smtp']['encryption'] === 'tls' ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = $config['smtp']['port'];

    // Try to connect (this will fail with auth error if credentials are wrong)
    $mail->smtpConnect();

    echo json_encode([
        'status' => 'success',
        'message' => 'SMTP connection successful'
    ]);

    $mail->smtpClose();

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'SMTP connection failed: ' . $e->getMessage(),
        'error_code' => $e->getCode(),
        'smtp_config' => [
            'host' => $config['smtp']['host'],
            'port' => $config['smtp']['port'],
            'username' => $config['smtp']['username'],
            'password_length' => strlen($config['smtp']['password']),
            'encryption' => $config['smtp']['encryption']
        ]
    ]);
}
?>