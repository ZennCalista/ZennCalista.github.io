<?php
/**
 * Download Backup File Script
 */

// Security check
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    die('Access denied');
}

$backupDir = __DIR__ . '/backups/';
$file = isset($_GET['file']) ? basename($_GET['file']) : '';

if (empty($file)) {
    die('No file specified');
}

$filePath = $backupDir . $file;

if (!file_exists($filePath)) {
    die('File not found');
}

// Send file as download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($filePath));
header('Pragma: public');

readfile($filePath);
exit;
?>
