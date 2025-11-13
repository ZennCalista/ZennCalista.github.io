<?php
include 'backend/db.php';

$programs = [
    [
        'program_name' => 'Community Health Program',
        'project_titles' => '["Health Education Workshop", "Medical Mission", "Nutrition Awareness Campaign"]',
        'department' => 'Health Sciences',
        'location' => 'Barangay San Jose',
        'start_date' => '2024-01-15',
        'end_date' => '2024-12-15',
        'status' => 'ended',
        'faculty_id' => 2,
        'description' => 'A comprehensive health program for the community'
    ],
    [
        'program_name' => 'Digital Literacy Training',
        'project_titles' => '["Basic Computer Skills", "Internet Safety Workshop", "Digital Tools for Seniors"]',
        'department' => 'Computer Science',
        'location' => 'Community Center',
        'start_date' => '2024-03-01',
        'end_date' => '2024-11-30',
        'status' => 'ended',
        'faculty_id' => 3,
        'description' => 'Training program to improve digital literacy in the community'
    ],
    [
        'program_name' => 'Environmental Conservation Project',
        'project_titles' => '["Tree Planting Activity", "Waste Management Workshop", "Clean River Campaign"]',
        'department' => 'Environmental Science',
        'location' => 'Various Locations',
        'start_date' => '2024-02-01',
        'end_date' => '2024-10-31',
        'status' => 'ongoing',
        'faculty_id' => 2,
        'description' => 'Environmental awareness and conservation activities'
    ]
];

foreach ($programs as $prog) {
    $stmt = $conn->prepare("INSERT IGNORE INTO programs (program_name, project_titles, department, location, start_date, end_date, status, faculty_id, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $prog['program_name'], $prog['project_titles'], $prog['department'], $prog['location'], $prog['start_date'], $prog['end_date'], $prog['status'], $prog['faculty_id'], $prog['description']);
    $stmt->execute();
    $stmt->close();
}

echo "Sample programs inserted\n";
?>