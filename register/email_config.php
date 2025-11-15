<?php
// Load environment variables for local development
require_once __DIR__ . '/../env_loader.php';

// Email configuration for OTP system
// CONFIGURE THESE SETTINGS WITH YOUR SENDGRID API KEY FOR PRODUCTION
//
// SendGrid Setup (Recommended for Heroku):
// 1. Sign up for SendGrid (free tier available)
// 2. Create an API key in SendGrid dashboard
// 3. Set the SENDGRID_API_KEY environment variable in your deployment platform (Heroku/Railway)
// 4. Verify your sender email in SendGrid
//
// For local testing, create a .env file in the project root with:
// SENDGRID_API_KEY=your-actual-api-key-here
//
// For production deployment:
// - Heroku: heroku config:set SENDGRID_API_KEY=your-key-here
// - Railway: Set environment variable in dashboard
//
// For local testing, keep the Gmail settings if preferred

return [
    'smtp' => [
        'host' => 'smtp.sendgrid.net', // SendGrid SMTP host
        'port' => 587, // SMTP port
        'encryption' => 'tls', // 'tls' or 'ssl'
        'username' => 'apikey', // SendGrid uses 'apikey' as username
        'password' => getenv('SENDGRID_API_KEY') ?: 'your-sendgrid-api-key', // Read from environment variable
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