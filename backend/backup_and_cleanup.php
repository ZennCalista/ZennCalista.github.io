<?php
/**
 * Database Backup and Cleanup Script
 * 
 * This script:
 * 1. Creates a complete SQL backup of the database
 * 2. Downloads the backup file
 * 3. Optionally deletes data from: images, images_archive, programs, programs_archive tables
 * 
 * Usage: 
 * - To backup only: backup_and_cleanup.php
 * - To backup and delete: backup_and_cleanup.php?delete=1
 */

// Security: Only allow from localhost or specific IP
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    die('Access denied. This script can only be run from localhost.');
}

require_once 'db.php';

// Configuration
$backupDir = __DIR__ . '/backups/';
$deleteData = isset($_GET['delete']) && $_GET['delete'] == '1';

// Create backups directory if it doesn't exist
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Generate backup filename with timestamp
$timestamp = date('Y-m-d_H-i-s');
$backupFile = $backupDir . 'etracker_backup_' . $timestamp . '.sql';

// Function to export database to SQL file
function exportDatabase($conn, $backupFile, $dbname) {
    $output = "-- Database Backup\n";
    $output .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    // Get all tables
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    // Loop through each table
    foreach ($tables as $table) {
        $output .= "-- Table: $table\n";
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        
        // Get CREATE TABLE statement
        $createResult = $conn->query("SHOW CREATE TABLE `$table`");
        $createRow = $createResult->fetch_row();
        $output .= $createRow[1] . ";\n\n";
        
        // Get table data
        $dataResult = $conn->query("SELECT * FROM `$table`");
        if ($dataResult->num_rows > 0) {
            while ($row = $dataResult->fetch_assoc()) {
                $columns = array_keys($row);
                $values = array_map(function($value) use ($conn) {
                    if ($value === null) return 'NULL';
                    return "'" . $conn->real_escape_string($value) . "'";
                }, array_values($row));
                
                $output .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
            }
            $output .= "\n";
        }
    }
    
    $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    // Write to file
    file_put_contents($backupFile, $output);
    return true;
}

// Function to delete data from specified tables
function cleanupTables($conn) {
    $tables = ['images', 'images_archive', 'programs', 'programs_archive'];
    $results = [];
    
    foreach ($tables as $table) {
        $query = "DELETE FROM `$table`";
        if ($conn->query($query)) {
            $results[$table] = [
                'success' => true,
                'affected_rows' => $conn->affected_rows
            ];
        } else {
            $results[$table] = [
                'success' => false,
                'error' => $conn->error
            ];
        }
    }
    
    return $results;
}

// Start output
echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Backup & Cleanup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #054634; }
        .success { color: green; padding: 10px; background: #e8f5e9; border-radius: 4px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffebee; border-radius: 4px; margin: 10px 0; }
        .info { color: #1976d2; padding: 10px; background: #e3f2fd; border-radius: 4px; margin: 10px 0; }
        .warning { color: #f57c00; padding: 10px; background: #fff3e0; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #054634; color: white; }
        .btn { display: inline-block; padding: 10px 20px; background: #054634; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
        .btn:hover { background: #076d4f; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
    </style>
</head>
<body>
    <h1>Database Backup & Cleanup Tool</h1>
";

try {
    // Step 1: Create backup
    echo "<div class='info'><strong>Step 1:</strong> Creating database backup...</div>";
    
    if (exportDatabase($conn, $backupFile, 'etracker')) {
        $fileSize = filesize($backupFile);
        $fileSizeMB = round($fileSize / 1024 / 1024, 2);
        
        echo "<div class='success'><strong> Backup created successfully!</strong><br>";
        echo "File: " . basename($backupFile) . "<br>";
        echo "Size: $fileSizeMB MB<br>";
        echo "Location: $backupFile</div>";
        
        // Provide download link
        echo "<a href='download_backup.php?file=" . urlencode(basename($backupFile)) . "' class='btn'>Download Backup</a>";
    } else {
        throw new Exception("Failed to create backup");
    }
    
    // Step 2: Delete data if requested
    if ($deleteData) {
        echo "<div class='warning'><strong>Step 2:</strong> Deleting data from specified tables...</div>";
        
        $results = cleanupTables($conn);
        
        echo "<table>";
        echo "<tr><th>Table</th><th>Status</th><th>Rows Deleted</th></tr>";
        
        foreach ($results as $table => $result) {
            echo "<tr>";
            echo "<td>$table</td>";
            if ($result['success']) {
                echo "<td style='color: green;'> Success</td>";
                echo "<td>" . $result['affected_rows'] . "</td>";
            } else {
                echo "<td style='color: red;'> Failed</td>";
                echo "<td>" . $result['error'] . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<div class='success'><strong> Cleanup completed!</strong></div>";
    } else {
        echo "<div class='info'><strong>Note:</strong> No data was deleted. To delete data from tables, add <code>?delete=1</code> to the URL.</div>";
        echo "<a href='?delete=1' class='btn btn-danger' onclick='return confirm(\"Are you sure you want to delete data from: images, images_archive, programs, programs_archive?\")'>Backup & Delete Data</a>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'><strong>Error:</strong> " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<p><a href='list_backups.php' class='btn'>View All Backups</a></p>";
echo "</body></html>";

$conn->close();
?>
