<?php

header('Content-Type: application/json');
require_once 'db.php';

// Total Students
$students = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student'")->fetch_assoc()['total'];

// Total Faculty
$faculty = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='faculty'")->fetch_assoc()['total'];

// Ongoing Programs
$programs = $conn->query("SELECT COUNT(*) as total FROM programs WHERE status='ongoing'")->fetch_assoc()['total'];

// Certificates Issued (count all participants as issued)
$certificates = $conn->query("SELECT COUNT(*) as total FROM participants")->fetch_assoc()['total'];

// Attendance Rate (placeholder, since no attendance table)
$attendance = 85; // Placeholder value

// Upcoming Sessions (use program start_date)
$sessions = [];
$res = $conn->query("SELECT start_date as date, program_name 
    FROM programs 
    WHERE start_date >= CURDATE() 
    ORDER BY start_date ASC LIMIT 3");
while ($row = $res->fetch_assoc()) $sessions[] = $row;

// Feedback Highlights (placeholder, since no detailed_evaluations)
$feedback = ["The program was well-organized.", "Great learning experience.", "Looking forward to more sessions."];

// Program Trends (enrollment from participants)
$trends = ['labels'=>[], 'data'=>[]];
$res = $conn->query("SELECT p.program_name, COUNT(part.program_id) as enrolled 
    FROM programs p 
    LEFT JOIN participants part ON p.id = part.program_id 
    GROUP BY p.id 
    ORDER BY enrolled DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
    $trends['labels'][] = $row['program_name'];
    $trends['data'][] = (int)$row['enrolled'];
}

echo json_encode([
    'students' => (int)$students,
    'faculty' => (int)$faculty,
    'programs' => (int)$programs,
    'certificates' => (int)$certificates,
    'attendanceRate' => round($attendance),
    'upcomingSessions' => $sessions,
    'feedback' => $feedback,
    'programTrends' => $trends
]);