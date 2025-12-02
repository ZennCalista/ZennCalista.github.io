# Password Change Feature Documentation

## Overview
Secure password change functionality with OTP (One-Time Password) verification for the eTracker system. This feature can be integrated into any user profile (Faculty, Student, Admin).

## Features
-  Three-step wizard interface
-  Current password verification
-  OTP email verification (6-digit code, 10-minute expiry)
-  Real-time password strength validation
-  Password match indicator
-  Countdown timer for OTP expiry
-  Resend OTP functionality
-  Password change history logging
-  Responsive design with animations
-  Reusable components for all user levels

## Architecture

### Components
```
portal/shared/
 password_utils.php            # Server-side validation & database operations
 password_change_modal.php     # HTML/CSS modal UI
 password_change_modal.js      # Client-side logic & AJAX
 change_password_request.php   # Backend: Verify password & send OTP
 change_password_verify.php    # Backend: Verify OTP & change password
```

### Database Tables
1. **otp_verifications** (existing)
   - Stores OTP codes with expiration
   - Fields: user_id, email, otp_code, expires_at

2. **password_change_history** (new)
   - Logs all password changes
   - Fields: id, user_id, ip_address, user_agent, changed_at

## Password Requirements
- Minimum 8 characters
- At least one uppercase letter (A-Z)
- At least one lowercase letter (a-z)
- At least one number (0-9)

## Implementation Flow

### Step 1: Verify Current Password
1. User enters current password
2. Frontend sends AJAX request to `change_password_request.php`
3. Backend verifies password against database
4. If valid: Generate 6-digit OTP
5. Store OTP in database with 10-minute expiry
6. Send OTP via email using PHPMailer
7. Return success and email address to frontend

### Step 2: OTP Verification
1. Display OTP input field with countdown timer
2. User enters 6-digit code received via email
3. Timer shows remaining time (format: MM:SS)
4. "Resend OTP" button enabled after expiry
5. User proceeds to password change step

### Step 3: Change Password
1. User enters new password
2. Real-time validation shows requirement checklist:
   -  At least 8 characters
   -  One uppercase letter
   -  One lowercase letter
   -  One number
3. User confirms new password
4. Match indicator shows if passwords match
5. Frontend validates all requirements
6. AJAX request to `change_password_verify.php` with OTP and new password
7. Backend verifies OTP validity
8. Backend validates password requirements
9. Backend checks new password != current password
10. Backend updates password hash
11. Backend logs change in password_change_history
12. Backend deletes used OTP
13. User logged out and redirected to login

## Integration Guide

### For Faculty Profile (Completed)
```php
// In FACULTY/profile.php

// Add Security Settings card
<div class="profile-card-section">
  <div class="section-header">
    <i class="fas fa-shield-alt"></i>
    <span>Security Settings</span>
  </div>
  <div class="section-content">
    <div class="info-row">
      <span>Password</span>
      <button class="change-password-btn" onclick="PasswordChangeModal.open()">
        <i class="fas fa-key"></i> Change Password
      </button>
    </div>
  </div>
</div>

// Before </body> tag
<?php include '../portal/shared/password_change_modal.php'; ?>
<script src="../portal/shared/password_change_modal.js"></script>
```

### For Student Profile (Pending)
```php
// In STUDENT/Profile.php
// Follow same structure as Faculty profile
// Add Security Settings card
// Include modal PHP and JS files
```

### For Admin Profile (Future)
```php
// In ADMIN/[profile_page].php
// Follow same structure
// Include modal PHP and JS files
```

## API Endpoints

### POST /portal/shared/change_password_request.php
**Request:**
```json
{
  "current_password": "string"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "OTP sent to your email",
  "email": "user@example.com",
  "expires_in": 600
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Current password is incorrect"
}
```

### POST /portal/shared/change_password_verify.php
**Request:**
```json
{
  "otp": "123456",
  "new_password": "NewPass123",
  "confirm_password": "NewPass123"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Password changed successfully"
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Invalid or expired OTP",
  "errors": ["Optional array of validation errors"]
}
```

## Security Features
1. **Session-based authentication**: Only logged-in users can change password
2. **Current password verification**: Prevents unauthorized changes
3. **OTP verification**: Two-factor authentication via email
4. **Time-limited OTP**: 10-minute expiry prevents replay attacks
5. **Password hashing**: BCrypt hashing with PASSWORD_DEFAULT
6. **Prevent password reuse**: New password must differ from current
7. **Activity logging**: All changes logged with IP and user agent
8. **Input sanitization**: SQL injection protection with prepared statements
9. **Rate limiting ready**: Can add throttling to OTP requests

## Testing Checklist
- [ ] Modal opens correctly when button clicked
- [ ] Step 1: Invalid current password shows error
- [ ] Step 1: Valid current password sends OTP
- [ ] Email received with 6-digit OTP code
- [ ] Step 2: OTP countdown timer works correctly
- [ ] Step 2: Resend OTP button enables after expiry
- [ ] Step 2: Invalid OTP format shows error
- [ ] Step 3: Password requirements update in real-time
- [ ] Step 3: Password match indicator works
- [ ] Step 3: Weak passwords rejected
- [ ] Step 3: Valid password change succeeds
- [ ] Password change logged in database
- [ ] User redirected to login after success
- [ ] Can login with new password

## Troubleshooting

### OTP not received
- Check PHPMailer configuration in `register/otp_utils.php`
- Verify SendGrid API key is valid
- Check spam folder
- Ensure email address is correct in session

### OTP expired immediately
- Check server timezone settings
- Verify database time matches server time
- Check expires_at calculation in `register/otp_utils.php`

### Password validation fails
- Ensure password meets all 4 requirements
- Check console for JavaScript errors
- Verify validatePassword() function works

### Database errors
- Run migration: `ADMIN/run_password_history_migration.php`
- Check database connection in db.php files
- Verify users table has password column

## Future Enhancements
1. Add password strength meter with color indicators
2. Add "Remember me on this device" option
3. Add password history to prevent reusing last N passwords
4. Add email notification when password changed
5. Add SMS OTP option
6. Add password expiry reminders
7. Add admin panel to view password change logs

## Files Modified
-  `FACULTY/profile.php` - Added Security Settings card and modal includes
-  Created `portal/shared/password_utils.php`
-  Created `portal/shared/password_change_modal.php`
-  Created `portal/shared/password_change_modal.js`
-  Created `portal/shared/change_password_request.php`
-  Created `portal/shared/change_password_verify.php`
-  Created `ADMIN/create_password_history_table.sql`
-  Created `ADMIN/run_password_history_migration.php`
-  Created `test_password_change.php`

## Deployment Steps
1.  Create all component files in `portal/shared/`
2.  Run database migration for password_change_history table
3.  Integrate into Faculty profile
4.  Test complete flow end-to-end
5.  Integrate into Student profile
6.  Push to production
7.  Monitor error logs

## Support
For issues or questions, contact the development team.

---
**Version:** 1.0  
**Last Updated:** 2024  
**Author:** eTracker Development Team
