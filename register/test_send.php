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

    // Server settings
    $mail->isSMTP();
    $mail->Host = $config['smtp']['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp']['username'];
    $mail->Password = $config['smtp']['password'];
    $mail->SMTPSecure = $config['smtp']['encryption'] === 'tls' ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = $config['smtp']['port'];

    // Recipients - use a test email
    $mail->setFrom($config['smtp']['from_email'], $config['smtp']['from_name']);
    $mail->addAddress('test@example.com', 'Test User'); // Test address

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from eTracker';
    $mail->Body = '<h1>Test Email</h1><p>This is a test email from eTracker system.</p>';
    $mail->AltBody = 'This is a test email from eTracker system.';

    $mail->send();

    echo json_encode([
        'status' => 'success',
        'message' => 'Test email sent successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email sending failed: ' . $e->getMessage(),
        'error_info' => $mail->ErrorInfo ?? 'No additional error info',
        'smtp_config' => [
            'host' => $config['smtp']['host'],
            'port' => $config['smtp']['port'],
            'username' => $config['smtp']['username'],
            'from_email' => $config['smtp']['from_email']
        ]
    ]);
}
?>