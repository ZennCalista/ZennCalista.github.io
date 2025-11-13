<?php
include 'register/db.php';

echo "users table:\n";
$result = $conn->query('DESCRIBE users');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' ' . $row['Type'] . ' ' . ($row['Null'] == 'NO' ? 'NOT NULL' : 'NULL') . ' ' . ($row['Key'] ? $row['Key'] : '') . ' ' . ($row['Default'] ? 'DEFAULT ' . $row['Default'] : '') . ' ' . ($row['Extra'] ? $row['Extra'] : '') . "\n";
}
echo "\n";

echo "departments table:\n";
$result = $conn->query('DESCRIBE departments');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' ' . $row['Type'] . ' ' . ($row['Null'] == 'NO' ? 'NOT NULL' : 'NULL') . ' ' . ($row['Key'] ? $row['Key'] : '') . ' ' . ($row['Default'] ? 'DEFAULT ' . $row['Default'] : '') . ' ' . ($row['Extra'] ? $row['Extra'] : '') . "\n";
    }
} else {
    echo "departments table does not exist\n";
}
echo "\n";

echo "faculty table:\n";
$result = $conn->query('DESCRIBE faculty');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' ' . $row['Type'] . ' ' . ($row['Null'] == 'NO' ? 'NOT NULL' : 'NULL') . ' ' . ($row['Key'] ? $row['Key'] : '') . ' ' . ($row['Default'] ? 'DEFAULT ' . $row['Default'] : '') . ' ' . ($row['Extra'] ? $row['Extra'] : '') . "\n";
    }
} else {
    echo "faculty table does not exist\n";
}
?>