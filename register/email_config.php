<?php
// Email configuration for OTP system
// CURRENTLY IN TESTING MODE - OTP codes will be logged instead of sent via email
//
// To enable real email sending, configure these settings with your email provider credentials:
// 1. For Gmail: Use App Passwords (not your regular password)
// 2. For Outlook: Use your account password
// 3. Update all 'your-*' placeholders with actual values

return [
    'smtp' => [
        'host' => 'smtp.gmail.com', // SMTP host (gmail, outlook, etc.)
        'port' => 587, // SMTP port (587 for TLS, 465 for SSL)
        'encryption' => 'tls', // 'tls' or 'ssl'
        'username' => 'your-email@gmail.com', // Your email address - CHANGE THIS
        'password' => 'your-app-password', // Your email password or app password - CHANGE THIS
        'from_email' => 'your-email@gmail.com', // From email address - CHANGE THIS
        'from_name' => 'eTracker System' // From name
    ],
    'otp' => [
        'length' => 6, // OTP code length
        'expiry_minutes' => 10, // OTP expiry time in minutes
        'max_attempts_per_hour' => 3 // Max OTP requests per hour per user
    ]
];
?>