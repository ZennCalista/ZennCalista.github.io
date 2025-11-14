<?php
require_once 'FACULTY/db.php';

$result = $conn->query('SHOW TABLES LIKE "document_uploads"');
if ($result->num_rows > 0) {
    echo "document_uploads table exists\n";
    $result2 = $conn->query('DESCRIBE document_uploads');
    echo "Table structure:\n";
    while ($row = $result2->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} else {
    echo "document_uploads table does not exist\n";
}
?>