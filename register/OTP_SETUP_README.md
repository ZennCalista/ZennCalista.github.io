# Email OTP Verification Setup Guide

This guide explains how to set up email OTP verification for the eTracker registration system.

## Prerequisites

1. **PHPMailer Library**: Already installed via Composer
2. **Database Table**: OTP verification table created
3. **Email Account**: Gmail or other SMTP provider account

## Configuration Steps

### 1. Configure Email Settings

Edit `email_config.php` with your email provider settings:

```php
<?php
return [
    'smtp' => [
        'host' => 'smtp.gmail.com', // Your SMTP host
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
```

### 2. Gmail Setup (if using Gmail)

1. **Enable 2-Factor Authentication** on your Google account
2. **Generate App Password**:
   - Go to Google Account settings
   - Security → 2-Step Verification → App passwords
   - Generate password for "Mail"
   - Use this app password in the `password` field above

### 3. Alternative Email Providers

#### Outlook/Hotmail:
```php
'host' => 'smtp-mail.outlook.com',
'port' => 587,
'encryption' => 'tls',
```

#### Yahoo:
```php
'host' => 'smtp.mail.yahoo.com',
'port' => 587,
'encryption' => 'tls',
```

#### Custom SMTP:
```php
'host' => 'your-smtp-server.com',
'port' => 587, // or 465
'encryption' => 'tls', // or 'ssl'
```

## Testing the Setup

### 1. Test Email Configuration

Create a test file to verify email sending:

```php
<?php
include 'otp_utils.php';
include 'db.php';

$otp_utils = new OTPUtils($conn);
$result = $otp_utils->sendOTP('test@example.com', '123456');
echo json_encode($result);
?>
```

### 2. Test Database Connection

Run the setup script to ensure database tables exist:

```bash
php setup_otp_table.php
```

### 3. Test Full Registration Flow

1. Open `index.html` in browser
2. Fill registration form
3. Submit and check for OTP email
4. Enter OTP code
5. Complete role-specific registration

## Security Features

- **OTP Expiration**: Codes expire after 10 minutes
- **Rate Limiting**: Max 3 OTP requests per hour per user
- **One-time Use**: Each OTP can only be used once
- **Secure Storage**: OTPs are hashed in database
- **Email Validation**: Proper email format validation

## Troubleshooting

### Common Issues:

1. **"Failed to send OTP email"**
   - Check email credentials in `email_config.php`
   - Verify SMTP settings for your provider
   - Check if less secure apps are enabled (Gmail)

2. **"Too many OTP requests"**
   - User has exceeded rate limit (3 per hour)
   - Wait or contact admin to reset

3. **"Invalid or expired OTP code"**
   - Code may have expired (10 minutes)
   - Code was already used
   - Incorrect code entered

4. **Database Connection Issues**
   - Verify AWS RDS credentials in `db.php`
   - Check if `otp_verifications` table exists

### Debug Mode

Enable debug logging by adding to `otp_utils.php`:

```php
$mail->SMTPDebug = 2; // Enable verbose debug output
```

## File Structure

```
register/
├── email_config.php      # Email configuration
├── otp_utils.php         # OTP utility functions
├── send_otp.php          # API endpoint for sending OTP
├── verify_otp.php        # API endpoint for verifying OTP
├── setup_otp_table.php   # Database setup script
├── create_otp_table.sql  # SQL for table creation
└── OTP_SETUP_README.md   # This file
```

## Registration Flow

1. **Initial Registration**: User enters basic info → Account created (unverified)
2. **OTP Verification**: System sends 6-digit code to email → User enters code
3. **Email Verified**: Account marked as verified → Proceed to role details
4. **Complete Registration**: User fills role-specific information → Registration complete

The OTP step ensures email ownership verification before allowing full account access.