<?php
$conn = new mysqli('localhost', 'root', '', 'etracker');
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

// Create program_proposals table
$sql = "CREATE TABLE IF NOT EXISTS program_proposals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    faculty_id INT NOT NULL,
    proposal_title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending', 'approved', 'rejected', 'used') DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by INT NULL,
    review_notes TEXT,
    program_id INT NULL,

    FOREIGN KEY (faculty_id) REFERENCES faculty(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id),
    FOREIGN KEY (program_id) REFERENCES programs(id)
)";
if ($conn->query($sql)) {
    echo "✓ program_proposals table created successfully\n";
} else {
    echo "✗ Error creating program_proposals table: " . $conn->error . "\n";
}

// Add proposal_id column to document_uploads if it doesn't exist
$check_column = $conn->query("SHOW COLUMNS FROM document_uploads LIKE 'proposal_id'");
if ($check_column->num_rows == 0) {
    $alter_sql = "ALTER TABLE document_uploads ADD COLUMN proposal_id INT NULL, ADD FOREIGN KEY (proposal_id) REFERENCES program_proposals(id)";
    if ($conn->query($alter_sql)) {
        echo "✓ proposal_id column added to document_uploads table\n";
    } else {
        echo "✗ Error adding proposal_id column: " . $conn->error . "\n";
    }
} else {
    echo "✓ proposal_id column already exists in document_uploads table\n";
}

$conn->close();
?>