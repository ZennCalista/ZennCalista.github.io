<?php
// debug_session.php - Simple session debug
require_once __DIR__ . '/session_config.php';
session_start();

header('Content-Type: application/json');
echo json_encode([
    'session_name' => session_name(),
    'session_id' => session_id(),
    'cookie_path' => ini_get('session.cookie_path'),
    'session_data' => \,
    'has_user' => isset(\['user']),
    'has_role' => isset(\['role']),
    'cookies' => \
], JSON_PRETTY_PRINT);
