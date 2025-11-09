<?php
/**
 * List All Backup Files
 */

// Security check
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    die('Access denied');
}

$backupDir = __DIR__ . '/backups/';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Backup Files</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; }
        h1 { color: #054634; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #054634; color: white; }
        tr:hover { background: #f5f5f5; }
        .btn { display: inline-block; padding: 8px 16px; background: #054634; color: white; text-decoration: none; border-radius: 4px; margin: 2px; }
        .btn:hover { background: #076d4f; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
    </style>
</head>
<body>
    <h1>Database Backups</h1>
    <p><a href='backup_and_cleanup.php' class='btn'>Create New Backup</a></p>
    
    <?php
    if (!is_dir($backupDir)) {
        echo "<p>No backups directory found.</p>";
    } else {
        $files = array_diff(scandir($backupDir), ['.', '..']);
        $files = array_filter($files, function($file) use ($backupDir) {
            return is_file($backupDir . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'sql';
        });
        
        if (empty($files)) {
            echo "<p>No backup files found.</p>";
        } else {
            // Sort by modification time, newest first
            usort($files, function($a, $b) use ($backupDir) {
                return filemtime($backupDir . $b) - filemtime($backupDir . $a);
            });
            
            echo "<table>";
            echo "<tr><th>Filename</th><th>Size</th><th>Created</th><th>Actions</th></tr>";
            
            foreach ($files as $file) {
                $filePath = $backupDir . $file;
                $fileSize = filesize($filePath);
                $fileSizeMB = round($fileSize / 1024 / 1024, 2);
                $fileDate = date('Y-m-d H:i:s', filemtime($filePath));
                
                echo "<tr>";
                echo "<td>$file</td>";
                echo "<td>$fileSizeMB MB</td>";
                echo "<td>$fileDate</td>";
                echo "<td>";
                echo "<a href='download_backup.php?file=" . urlencode($file) . "' class='btn'>Download</a>";
                echo "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    }
    ?>
</body>
</html>
