# 🔐 Token-Based Authentication System

## Overview

The eTracker system now supports **multi-device authentication** using secure tokens. This allows users to stay logged in on multiple devices simultaneously without sessions interfering with each other.

---

## ✨ Features

### 1. **Multi-Device Support**
- Users can log in on multiple devices (desktop, mobile, tablet)
- Each device gets its own unique authentication token
- Logging out on one device doesn't affect other devices

### 2. **Session Limits**
- Maximum 5 active sessions per user
- Automatically removes oldest session when limit is exceeded
- Prevents abuse and resource exhaustion

### 3. **Token Expiry**
- Tokens expire after 30 days of inactivity
- "Remember Me" functionality built-in
- Automatic cleanup of expired tokens

### 4. **Security Features**
- Cryptographically secure random tokens (64 characters)
- HttpOnly cookies prevent JavaScript access (XSS protection)
- Device fingerprinting via User-Agent
- IP address tracking for security auditing
- No HTTPS requirement for development

### 5. **Backward Compatibility**
- Falls back to PHP sessions if tokens aren't available
- Gradual migration - existing users continue working
- No breaking changes to current functionality

---

## 📁 Files Added/Modified

### New Files:
```
backend/
├── create_tokens_table.sql          # Database migration
├── run_token_migration.php          # Migration runner
├── token_utils.php                  # Token utility functions
├── cleanup_tokens.php               # Expired token cleanup
├── token_admin.html                 # Admin management interface
└── TOKEN_SYSTEM_README.md           # This file
```

### Modified Files:
```
register/
├── login.php                        # Now generates tokens
└── logout.php                       # Invalidates current token only

portal/home/backend/
└── session_user.php                 # Token validation added
```

---

## 🚀 Setup Instructions

### Step 1: Run Database Migration

**Option A: Via Browser (Recommended)**
1. Open browser and navigate to:
   ```
   http://localhost/Etracker/backend/token_admin.html
   ```
2. Click **"Run Migration"** button
3. Wait for success message

**Option B: Via MySQL Client**
```bash
mysql -u admin -p etracker < backend/create_tokens_table.sql
```

### Step 2: Verify Installation

1. On the same admin page, click **"Refresh Stats"**
2. You should see:
   - Active Tokens: 0
   - Active Users: 0
   - Expired Tokens: 0

### Step 3: Test Multi-Device Login

1. **Device A (Desktop):**
   - Open http://localhost/Etracker
   - Login with your credentials
   - You should see your dashboard

2. **Device B (Incognito/Mobile):**
   - Open http://localhost/Etracker in incognito/different browser
   - Login with THE SAME credentials
   - Both sessions should work simultaneously ✓

3. **Test Logout:**
   - Logout from Device A
   - Device B should STILL be logged in ✓

---

## 🔧 Configuration

### Token Expiry Duration

Edit `login.php` line ~65:
```php
$token = createAuthToken($conn, $user['id'], 30); // 30 days
```

Change `30` to desired number of days.

### Session Limit

Edit `token_utils.php` function `checkSessionLimit()`:
```php
function checkSessionLimit($conn, $userId, $maxSessions = 5) {
    // Change 5 to desired limit
}
```

### HTTPS Requirement

Currently disabled for development. To enable in production:

Edit `token_utils.php` function `setAuthCookie()`:
```php
function setAuthCookie($token, $expiryDays = 30, $secure = false) {
    // Change false to true for HTTPS-only cookies
}
```

And update `login.php`:
```php
setAuthCookie($token, 30, true); // Enable secure flag
```

---

## 📊 Database Schema

### `user_tokens` Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `user_id` | INT | Foreign key to users table |
| `token` | VARCHAR(255) | Unique authentication token |
| `device_info` | VARCHAR(500) | User agent string |
| `ip_address` | VARCHAR(45) | IP address of device |
| `created_at` | TIMESTAMP | Token creation time |
| `expires_at` | TIMESTAMP | Token expiration time |
| `last_activity` | TIMESTAMP | Last time token was used |

**Indexes:**
- `idx_token` - Fast token lookup
- `idx_user_id` - Fast user token queries
- `idx_expires_at` - Fast cleanup queries
- `idx_user_expires` - Composite index for session limits

---

## 🛠️ Maintenance

### Automatic Cleanup (Recommended)

**Option 1: Cron Job (Linux)**
```bash
# Add to crontab (runs daily at 3 AM)
0 3 * * * php /path/to/Etracker/backend/cleanup_tokens.php
```

**Option 2: Windows Task Scheduler**
1. Open Task Scheduler
2. Create Basic Task
3. Trigger: Daily at 3:00 AM
4. Action: Start a program
5. Program: `C:\xampp\php\php.exe`
6. Arguments: `E:\xampp\htdocs\Etracker\backend\cleanup_tokens.php`

**Option 3: Manual via Browser**
```
http://localhost/Etracker/backend/token_admin.html
```
Click "Cleanup Tokens" button

---

## 🔍 Troubleshooting

### Problem: "Token not working after login"

**Solution:** Check browser cookies are enabled
```javascript
// Test in browser console:
document.cookie
// Should show: auth_token=...
```

### Problem: "Logged out unexpectedly"

**Possible Causes:**
1. Token expired (check expires_at in database)
2. Browser cleared cookies
3. Someone else logged in 5+ times (session limit)

**Solution:** Just login again - new token will be created

### Problem: "Migration fails"

**Check:**
1. Database connection in `backend/db.php`
2. User has CREATE TABLE permissions
3. Table doesn't already exist:
   ```sql
   SHOW TABLES LIKE 'user_tokens';
   ```

### Problem: "Still logging out all devices"

**Check:**
1. Make sure migration ran successfully
2. Clear browser cache and cookies
3. Check error logs:
   ```
   xampp/apache/logs/error.log
   ```

---

## 📈 Monitoring

### View Active Sessions

```sql
SELECT 
    u.email,
    u.role,
    t.device_info,
    t.ip_address,
    t.last_activity,
    t.expires_at
FROM user_tokens t
JOIN users u ON t.user_id = u.id
WHERE t.expires_at > NOW()
ORDER BY t.last_activity DESC;
```

### Count Tokens Per User

```sql
SELECT 
    u.email,
    COUNT(t.id) as token_count
FROM user_tokens t
JOIN users u ON t.user_id = u.id
WHERE t.expires_at > NOW()
GROUP BY u.email
ORDER BY token_count DESC;
```

---

## 🔒 Security Considerations

### ✅ Implemented

- ✓ Cryptographically secure tokens (random_bytes)
- ✓ HttpOnly cookies (XSS protection)
- ✓ Token expiration (30 days)
- ✓ Session limits (5 devices max)
- ✓ Last activity tracking
- ✓ IP address logging
- ✓ Device fingerprinting

### ⚠️ Recommendations for Production

1. **Enable HTTPS**
   - Get SSL certificate (Let's Encrypt is free)
   - Set `secure` flag in cookies

2. **Rate Limiting**
   - Limit login attempts per IP
   - Prevent brute force attacks

3. **Email Notifications**
   - Notify users of new device logins
   - Send security alerts

4. **Token Rotation**
   - Rotate tokens every 7 days
   - Force re-authentication periodically

5. **Suspicious Activity Detection**
   - Alert on IP changes
   - Flag concurrent logins from different countries

---

## 📚 API Reference

### Token Utility Functions

#### `generateToken()`
Generates a 64-character hexadecimal token.
```php
$token = generateToken();
// Returns: "a1b2c3d4e5f6..."
```

#### `createAuthToken($conn, $userId, $expiryDays)`
Creates and stores a new token for a user.
```php
$token = createAuthToken($conn, 123, 30);
if ($token) {
    setAuthCookie($token, 30, false);
}
```

#### `validateToken($conn, $token)`
Validates a token and returns user data.
```php
$user = validateToken($conn, $token);
if ($user) {
    echo "Welcome " . $user['firstname'];
}
```

#### `invalidateToken($conn, $token)`
Removes a specific token (logout current device).
```php
invalidateToken($conn, $token);
clearAuthCookie();
```

#### `invalidateAllUserTokens($conn, $userId)`
Removes all tokens for a user (logout all devices).
```php
invalidateAllUserTokens($conn, 123);
```

#### `cleanupExpiredTokens($conn)`
Deletes all expired tokens.
```php
$deleted = cleanupExpiredTokens($conn);
echo "Deleted $deleted tokens";
```

---

## 🎯 Benefits Over Session-Only

| Feature | PHP Sessions Only | Token System |
|---------|-------------------|--------------|
| Multi-device | ❌ Shared session | ✅ Independent tokens |
| Logout one device | ❌ Logs out all | ✅ Logout current only |
| Remember me | ❌ Complex | ✅ Built-in |
| Session tracking | ❌ Limited | ✅ Full device history |
| Security audit | ❌ No logs | ✅ IP + device tracking |
| Scalability | ❌ File-based | ✅ Database-backed |

---

## 📞 Support

For issues or questions:
1. Check error logs: `xampp/apache/logs/error.log`
2. Review this documentation
3. Contact system administrator

---

## 📝 Changelog

### Version 1.0 (2025-11-11)
- ✅ Initial token system implementation
- ✅ Multi-device authentication
- ✅ Session limits (5 devices)
- ✅ Automatic token cleanup
- ✅ Admin interface
- ✅ Backward compatibility with PHP sessions

---

**Last Updated:** November 11, 2025  
**Author:** System Administrator  
**Version:** 1.0
