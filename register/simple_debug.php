<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

try {
    // Load environment variables
    require_once __DIR__ . '/../env_loader.php';

    // Load config
    $config = require 'email_config.php';

    // Test database connection
    include 'db.php';

    echo json_encode([
        'status' => 'success',
        'debug_info' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => phpversion(),
            'sendgrid_api_key_set' => getenv('SENDGRID_API_KEY') ? 'YES' : 'NO',
            'sendgrid_api_key_length' => getenv('SENDGRID_API_KEY') ? strlen(getenv('SENDGRID_API_KEY')) : 0,
            'smtp_password_set' => !empty($config['smtp']['password']) && $config['smtp']['password'] !== 'your-sendgrid-api-key',
            'smtp_host' => $config['smtp']['host'],
            'smtp_port' => $config['smtp']['port'],
            'smtp_username' => $config['smtp']['username'],
            'env_file_exists' => file_exists(__DIR__ . '/../.env'),
            'vendor_autoload_exists' => file_exists(__DIR__ . '/../vendor/autoload.php'),
            'db_connection' => isset($conn) ? 'Connected' : 'Failed',
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ]
    ]);

    if (isset($conn)) {
        $conn->close();
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>