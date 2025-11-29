<?php
require_once 'ADMIN/db.php';

$result = $conn->query('DESCRIBE document_uploads');
echo "document_uploads table structure:\n";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\nprogram_proposals table structure:\n";
$result = $conn->query('DESCRIBE program_proposals');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
?>