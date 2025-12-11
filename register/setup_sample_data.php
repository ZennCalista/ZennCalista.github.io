<?php
require_once '../FACULTY/db.php';

echo "=== STREAMLINED REGISTRATION - SAMPLE DATA SETUP ===\n\n";

echo "This script will help you add sample student and faculty data to test the streamlined registration.\n\n";

echo "INSTRUCTIONS:\n";
echo "1. Edit this file and add your sample data to the arrays below\n";
echo "2. Run this script to populate the master tables\n";
echo "3. Test registration with the emails you added\n\n";

// ===== EDIT THESE ARRAYS WITH YOUR SAMPLE DATA =====

$sampleStudents = [
    // Format: ['student_id' => 'ID', 'email' => 'student@cvsu.edu.ph', 'firstname' => 'First', 'middle_initial' => 'M', 'lastname' => 'Last', 'course' => 'Course Name']
    ['student_id' => '2022003', 'email' => 'ic.gerwin.alcober@cvsu.edu.ph', 'firstname' => 'Gerwin Dean', 'middle_initial' => 'S', 'lastname' => 'Alcober', 'course' => 'Bachelor of Science in Information Technology'],
    // Add more students here...
];

$sampleFaculty = [
    // Format: ['faculty_id' => 'ID', 'email' => 'faculty@cvsu.edu.ph', 'firstname' => 'First', 'middle_initial' => 'M', 'lastname' => 'Last', 'department' => 'Department', 'position' => 'Position']
    ['faculty_id' => 'FAC003', 'email' => 'ic.althea.delacruz@cvsu.edu.ph', 'firstname' => 'Althea', 'middle_initial' => '-', 'lastname' => 'dela Cruz', 'department' => 'Information Technology', 'position' => 'Associate Professor'],
    // Add more faculty here...
];

// ===== DO NOT EDIT BELOW THIS LINE =====

echo "Sample data configured:\n";
echo "- Students: " . count($sampleStudents) . " records\n";
echo "- Faculty: " . count($sampleFaculty) . " records\n\n";

if (count($sampleStudents) === 0 && count($sampleFaculty) === 0) {
    echo "⚠️  No sample data configured.\n\n";
    echo "To add sample data:\n";
    echo "1. Open this file in your editor\n";
    echo "2. Add records to the \$sampleStudents and \$sampleFaculty arrays above\n";
    echo "3. Save and run this script again\n\n";
    echo "Example student entry:\n";
    echo "['student_id' => '2021001', 'email' => 'student@cvsu.edu.ph', 'firstname' => 'Juan', 'middle_initial' => 'D', 'lastname' => 'Dela Cruz', 'course' => 'Bachelor of Science in Computer Science']\n\n";
    echo "Example faculty entry:\n";
    echo "['faculty_id' => 'FAC001', 'email' => 'faculty@cvsu.edu.ph', 'firstname' => 'Dr. Ana', 'middle_initial' => 'R', 'lastname' => 'Rodriguez', 'department' => 'Computer Studies', 'position' => 'Professor']\n\n";
    exit(0);
}

// Insert students
if (count($sampleStudents) > 0) {
    echo "Inserting student data...\n";
    $studentStmt = $conn->prepare("INSERT INTO master_students (student_id, email, firstname, middle_initial, lastname, course) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($sampleStudents as $student) {
        $studentStmt->bind_param("ssssss",
            $student['student_id'],
            $student['email'],
            $student['firstname'],
            $student['middle_initial'],
            $student['lastname'],
            $student['course']
        );

        if ($studentStmt->execute()) {
            echo "✓ Added student: " . $student['firstname'] . " " . $student['lastname'] . " (" . $student['email'] . ")\n";
        } else {
            echo "✗ Failed to add student " . $student['email'] . ": " . $conn->error . "\n";
        }
    }
    $studentStmt->close();
}

// Insert faculty
if (count($sampleFaculty) > 0) {
    echo "\nInserting faculty data...\n";
    $facultyStmt = $conn->prepare("INSERT INTO master_faculty (faculty_id, email, firstname, middle_initial, lastname, department, position) VALUES (?, ?, ?, ?, ?, ?, ?)");

    foreach ($sampleFaculty as $faculty) {
        $facultyStmt->bind_param("sssssss",
            $faculty['faculty_id'],
            $faculty['email'],
            $faculty['firstname'],
            $faculty['middle_initial'],
            $faculty['lastname'],
            $faculty['department'],
            $faculty['position']
        );

        if ($facultyStmt->execute()) {
            echo "✓ Added faculty: " . $faculty['firstname'] . " " . $faculty['lastname'] . " (" . $faculty['email'] . ")\n";
        } else {
            echo "✗ Failed to add faculty " . $faculty['email'] . ": " . $conn->error . "\n";
        }
    }
    $facultyStmt->close();
}

echo "\n=== SETUP COMPLETE ===\n\n";

// Show current data counts
$result = $conn->query("SELECT COUNT(*) as count FROM master_students");
$row = $result->fetch_assoc();
echo "Total students in master table: " . $row['count'] . "\n";
$result->free();

$result = $conn->query("SELECT COUNT(*) as count FROM master_faculty");
$row = $result->fetch_assoc();
echo "Total faculty in master table: " . $row['count'] . "\n";
$result->free();

echo "\n=== NEXT STEPS ===\n";
echo "1. Test the streamlined registration:\n";
echo "   - Go to: http://localhost/Etracker/register/\n";
echo "   - Click REGISTER\n";
echo "   - Enter one of the emails you added above\n";
echo "   - Complete the registration process\n\n";

echo "2. Available test emails:\n";
foreach ($sampleStudents as $student) {
    echo "   - " . $student['email'] . " (Student: " . $student['firstname'] . " " . $student['lastname'] . ")\n";
}
foreach ($sampleFaculty as $faculty) {
    echo "   - " . $faculty['email'] . " (Faculty: " . $faculty['firstname'] . " " . $faculty['lastname'] . ")\n";
}

echo "\n3. After successful registration, users will be redirected to their dashboard.\n\n";

$conn->close();
?>