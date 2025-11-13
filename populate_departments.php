<?php
include 'register/db.php';

$departments = [
    'Department of Hospitality Management',
    'Department of Language and Mass Communication',
    'Department of Physical Education',
    'Department of Social Sciences and Humanities',
    'Teacher Education Department',
    'Department of Administration - ENTREP',
    'Department of Administration - BSOA',
    'Department of Administration - BM',
    'Department of Computer Studies'
];

foreach ($departments as $dept) {
    $stmt = $conn->prepare("INSERT IGNORE INTO departments (department_name) VALUES (?)");
    $stmt->bind_param('s', $dept);
    $stmt->execute();
    $stmt->close();
}

echo "Departments populated\n";
?>