<?php
// Gateway for external links (portal) to open the correct eTracker dashboard
// Starts the session and redirects based on role, or sends to login page when unauthenticated
session_start();

// prefer explicit check for user/session
if (isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header('Location: ../ADMIN/Dashboard.html');
        exit();
    } elseif ($role === 'faculty') {
        header('Location: ../FACULTY/Dashboard.php');
        exit();
    } elseif ($role === 'student' || $role === 'non_acad') {
        header('Location: ../STUDENT/index.php');
        exit();
    } else {
        // Fallback for any other roles
        header('Location: ../STUDENT/index.php');
        exit();
    }
}

// Not authenticated -> show login page
header('Location: index.html');
exit();
