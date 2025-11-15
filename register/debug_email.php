<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

// Load environment variables
require_once __DIR__ . '/../env_loader.php';

// Load config
$config = require 'email_config.php';

echo json_encode([
    'status' => 'debug',
    'environment_check' => [
        'SENDGRID_API_KEY_set' => getenv('SENDGRID_API_KEY') ? 'YES' : 'NO',
        'SENDGRID_API_KEY_value' => getenv('SENDGRID_API_KEY') ? substr(getenv('SENDGRID_API_KEY'), 0, 10) . '...' : 'NOT SET',
        'smtp_password' => $config['smtp']['password'] ? substr($config['smtp']['password'], 0, 10) . '...' : 'EMPTY',
        'smtp_host' => $config['smtp']['host'],
        'smtp_port' => $config['smtp']['port'],
        'smtp_username' => $config['smtp']['username'],
        'env_file_exists' => file_exists(__DIR__ . '/../.env') ? 'YES' : 'NO',
        'vendor_autoload_exists' => file_exists(__DIR__ . '/../vendor/autoload.php') ? 'YES' : 'NO'
    ]
]);
?>