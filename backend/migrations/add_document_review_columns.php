<?php
/**
 * Migration: Add review columns to document_uploads table
 * Run this once to add the missing columns for document review functionality
 */

require_once '../db.php';

try {
    echo "Starting migration...\n";
    
    // Check if columns already exist
    $result = $conn->query("SHOW COLUMNS FROM document_uploads LIKE 'admin_remarks'");
    
    if ($result->num_rows == 0) {
        echo "Adding admin_remarks column...\n";
        $sql = "ALTER TABLE document_uploads 
                ADD COLUMN admin_remarks TEXT NULL AFTER status";
        
        if ($conn->query($sql)) {
            echo "✓ admin_remarks column added successfully\n";
        } else {
            throw new Exception("Failed to add admin_remarks: " . $conn->error);
        }
    } else {
        echo "✓ admin_remarks column already exists\n";
    }
    
    // Check if reviewed_by column exists
    $result = $conn->query("SHOW COLUMNS FROM document_uploads LIKE 'reviewed_by'");
    
    if ($result->num_rows == 0) {
        echo "Adding reviewed_by column...\n";
        $sql = "ALTER TABLE document_uploads 
                ADD COLUMN reviewed_by INT NULL AFTER admin_remarks,
                ADD FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL";
        
        if ($conn->query($sql)) {
            echo "✓ reviewed_by column added successfully\n";
        } else {
            throw new Exception("Failed to add reviewed_by: " . $conn->error);
        }
    } else {
        echo "✓ reviewed_by column already exists\n";
    }
    
    // Check if reviewed_at column exists
    $result = $conn->query("SHOW COLUMNS FROM document_uploads LIKE 'reviewed_at'");
    
    if ($result->num_rows == 0) {
        echo "Adding reviewed_at column...\n";
        $sql = "ALTER TABLE document_uploads 
                ADD COLUMN reviewed_at DATETIME NULL AFTER reviewed_by";
        
        if ($conn->query($sql)) {
            echo "✓ reviewed_at column added successfully\n";
        } else {
            throw new Exception("Failed to add reviewed_at: " . $conn->error);
        }
    } else {
        echo "✓ reviewed_at column already exists\n";
    }
    
    echo "\n✅ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>