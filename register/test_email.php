<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

try {
    // Load environment variables
    require_once __DIR__ . '/../env_loader.php';

    // Load config
    $config = require 'email_config.php';

    // Test PHPMailer loading
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        // Test basic setup without sending
        $mail->isSMTP();
        $mail->Host = $config['smtp']['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp']['username'];
        $mail->Password = $config['smtp']['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['smtp']['port'];

        echo json_encode([
            'status' => 'success',
            'message' => 'PHPMailer loaded successfully',
            'smtp_config' => [
                'host' => $config['smtp']['host'],
                'port' => $config['smtp']['port'],
                'username' => $config['smtp']['username'],
                'password_length' => strlen($config['smtp']['password']),
                'encryption' => $config['smtp']['encryption']
            ]
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'PHPMailer autoload.php not found'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Exception: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
?>