<?php
require_once 'ADMIN/db.php';

$result = $conn->query('SELECT file_path, original_filename FROM document_uploads WHERE file_path IS NOT NULL LIMIT 5');
echo "File paths in database:\n";
while ($row = $result->fetch_assoc()) {
    echo 'File path: ' . $row['file_path'] . ' | Original: ' . $row['original_filename'] . "\n";
}
?>