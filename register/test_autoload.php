<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

try {
    echo "Starting autoload test...\n";

    $autoload_path = __DIR__ . '/../vendor/autoload.php';
    echo "Autoload path: $autoload_path\n";
    echo "File exists: " . (file_exists($autoload_path) ? 'YES' : 'NO') . "\n";

    if (file_exists($autoload_path)) {
        echo "Loading autoload.php...\n";
        require_once $autoload_path;
        echo "Autoload loaded successfully\n";

        echo "Checking if PHPMailer class exists...\n";
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            echo "PHPMailer class found!\n";

            echo json_encode([
                'status' => 'success',
                'message' => 'PHPMailer autoload working correctly'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'PHPMailer class not found after autoload'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'vendor/autoload.php not found',
            'autoload_path' => $autoload_path
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