<?php
// Email configuration for OTP system
// Configure these settings with your email provider credentials

return [
    'smtp' => [
        'host' => 'smtp.gmail.com', // SMTP host (gmail, outlook, etc.)
        'port' => 587, // SMTP port (587 for TLS, 465 for SSL)
        'encryption' => 'tls', // 'tls' or 'ssl'
        'username' => 'your-email@gmail.com', // Your email address
        'password' => 'your-app-password', // Your email password or app password
        'from_email' => 'your-email@gmail.com', // From email address
        'from_name' => 'eTracker System' // From name
    ],
    'otp' => [
        'length' => 6, // OTP code length
        'expiry_minutes' => 10, // OTP expiry time in minutes
        'max_attempts_per_hour' => 3 // Max OTP requests per hour per user
    ]
];
?>