<?php
$host = 'database-1.ch0e2sa0mf0l.ap-southeast-2.rds.amazonaws.com';
$user = 'admin';
$pass = 'admin1234!';
$dbname = 'etracker';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo " Connected to database\n\n";
    
    // Check if coordinators table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'coordinators'");
    if ($stmt->rowCount() > 0) {
        echo " coordinators table exists\n";
        
        // Count records
        $count = $pdo->query("SELECT COUNT(*) FROM coordinators")->fetchColumn();
        echo "  Records: $count\n";
        
        // Show first few
        $records = $pdo->query("SELECT id, name, department FROM coordinators ORDER BY display_order LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($records as $rec) {
            echo "  - [{$rec['id']}] {$rec['name']} ({$rec['department']})\n";
        }
    } else {
        echo " coordinators table does NOT exist\n";
    }
    
    echo "\n";
    
    // Check if coordinator_images table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'coordinator_images'");
    if ($stmt->rowCount() > 0) {
        echo " coordinator_images table exists\n";
        $count = $pdo->query("SELECT COUNT(*) FROM coordinator_images")->fetchColumn();
        echo "  Records: $count\n";
    } else {
        echo " coordinator_images table does NOT exist\n";
    }
    
} catch (PDOException $e) {
    echo " Error: " . $e->getMessage() . "\n";
}
