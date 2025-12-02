<?php
// Run password_change_history table migration
require_once 'db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Password History Table Migration</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #1b472b; }
        .success { color: green; padding: 10px; background: #e8f5e9; border-left: 4px solid green; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffebee; border-left: 4px solid red; margin: 10px 0; }
        .info { color: #1976d2; padding: 10px; background: #e3f2fd; border-left: 4px solid #1976d2; margin: 10px 0; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
<div class='container'>
<h1> Password History Table Migration</h1>";

try {
    $sql = file_get_contents('create_password_history_table.sql');
    
    echo "<div class='info'><strong>Executing SQL:</strong><br><code>" . htmlspecialchars(substr($sql, 0, 200)) . "...</code></div>";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'> <strong>Success!</strong> password_change_history table created successfully.</div>";
        
        // Verify table was created
        $result = $conn->query("SHOW TABLES LIKE 'password_change_history'");
        if ($result && $result->num_rows > 0) {
            echo "<div class='success'> Verified: Table exists in database.</div>";
            
            // Show table structure
            $structure = $conn->query("DESCRIBE password_change_history");
            if ($structure) {
                echo "<h2>Table Structure:</h2>";
                echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
                echo "<tr style='background: #f5f5f5;'><th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Field</th><th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Type</th><th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Null</th><th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Key</th><th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Default</th></tr>";
                while ($row = $structure->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($row['Field']) . "</td>";
                    echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($row['Type']) . "</td>";
                    echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($row['Null']) . "</td>";
                    echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($row['Key']) . "</td>";
                    echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        
        echo "<h2> Migration Complete</h2>";
        echo "<p>The password_change_history table is now ready to log password changes.</p>";
        echo "<p><a href='../FACULTY/profile.php' style='display: inline-block; padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px;'>Go to Faculty Profile</a></p>";
        
    } else {
        echo "<div class='error'> <strong>Error:</strong> " . htmlspecialchars($conn->error) . "</div>";
    }

} catch (Exception $e) {
    echo "<div class='error'> <strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>
</body>
</html>";

$conn->close();
?>
