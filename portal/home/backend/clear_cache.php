<?php
/**
 * Cache Management Utility
 * Use this to clear or manage the API response cache
 * Access via: http://localhost/Etracker/portal/home/backend/clear_cache.php?action=clear
 */

include 'cache_helper.php';

$cache = new SimpleCache();
$action = isset($_GET['action']) ? $_GET['action'] : 'status';

header('Content-Type: application/json');

$response = ['success' => false];

switch ($action) {
    case 'clear':
        $cache->clear();
        $response = ['success' => true, 'message' => 'All cache cleared successfully'];
        break;
        
    case 'clean':
        $cleaned = $cache->cleanExpired();
        $response = ['success' => true, 'message' => "Cleaned $cleaned expired cache files"];
        break;
        
    case 'status':
        $cache_dir = __DIR__ . '/cache';
        $files = glob($cache_dir . '/*.cache');
        $total_size = 0;
        foreach ($files as $file) {
            $total_size += filesize($file);
        }
        $response = [
            'success' => true,
            'cache_files' => count($files),
            'total_size' => round($total_size / 1024, 2) . ' KB',
            'cache_directory' => $cache_dir
        ];
        break;
        
    default:
        $response = [
            'success' => false, 
            'message' => 'Invalid action. Use: clear, clean, or status'
        ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
