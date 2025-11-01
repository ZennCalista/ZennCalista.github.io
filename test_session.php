<?php
// test_session.php - Quick test to verify session sharing
require_once __DIR__ . '/session_config.php';
session_start();

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .info { background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .success { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>eTracker Session Test</h1>
    
    <div class=\"info\">
        <h3>Session Configuration:</h3>
        <pre>Session Name: <?php echo session_name(); ?></pre>
        <pre>Session ID: <?php echo session_id(); ?></pre>
        <pre>Cookie Path: <?php echo ini_get('session.cookie_path'); ?></pre>
    </div>
    
    <?php if (isset(\['user'])): ?>
        <div class=\"success\">
            <h3> User is logged in!</h3>
            <pre><?php print_r(\['user']); ?></pre>
            <p><strong>Role:</strong> <?php echo \['role'] ?? 'Not set'; ?></p>
        </div>
    <?php else: ?>
        <div class=\"error\">
            <h3> No user session found</h3>
            <p>Please <a href=\"register/index.html\">login</a> first.</p>
        </div>
    <?php endif; ?>
    
    <div class=\"info\">
        <h3>Full Session Data:</h3>
        <pre><?php print_r(\); ?></pre>
    </div>
    
    <p>
        <a href=\"register/index.html\">Login Page</a> | 
        <a href=\"register/logout.php\">Logout</a> | 
        <a href=\"portal/home/home.html\">Portal Home</a>
    </p>
</body>
</html>
