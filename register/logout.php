<?php
session_start();
// Unset all session variables
$_SESSION = [];
// Ensure session is cleared
session_unset();

// Delete session cookie if present - clear at root path to cover apps in subfolders
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    // clear cookie with original params
    setcookie(session_name(), '', time() - 42000,
        $params['path'] ?? '/', $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? false
    );
    // also clear at root path for broader coverage
    setcookie(session_name(), '', time() - 42000, '/', $_SERVER['HTTP_HOST'] ?? '', isset($_SERVER['HTTPS']), true);
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: index.html');
exit();
?>
