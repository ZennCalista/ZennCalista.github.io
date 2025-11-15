<?php
// Email configuration for OTP system
// CONFIGURE THESE SETTINGS WITH YOUR SENDGRID API KEY FOR PRODUCTION
//
// SendGrid Setup (Recommended for Heroku):
// 1. Sign up for SendGrid (free tier available)
// 2. Create an API key in SendGrid dashboard
// 3. Replace 'your-sendgrid-api-key' with your actual API key
// 4. Verify your sender email in SendGrid
//
// For local testing, keep the Gmail settings if preferred

return [
    'smtp' => [
        'host' => 'smtp.sendgrid.net', // SendGrid SMTP host
        'port' => 587, // SMTP port
        'encryption' => 'tls', // 'tls' or 'ssl'
        'username' => 'apikey', // SendGrid uses 'apikey' as username
        'password' => 'your-sendgrid-api-key', // Your SendGrid API key - CHANGE THIS
        'from_email' => 'ic.extensionservices@gmail.com', // From email address
        'from_name' => 'eTracker System' // From name
    ],
    'otp' => [
        'length' => 6, // OTP code length
        'expiry_minutes' => 10, // OTP expiry time in minutes
        'max_attempts_per_hour' => 3 // Max OTP requests per hour per user
    ]
];
?>