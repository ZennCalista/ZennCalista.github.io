<?php
// session_config.php - Common session configuration for all parts of the application
// Include this at the top of any file that calls session_start()

// Set session cookie to be accessible across the entire /Etracker directory
ini_set('session.cookie_path', '/Etracker/');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// Use the same session name across the entire application
session_name('ETRACKER_SESSION');
