# 🚀 Token System - Quick Start Guide

## What Changed?

Your eTracker system now has **multi-device authentication**! Users can stay logged in on multiple devices without logging each other out.

---

## Setup (3 Minutes)

### Step 1: Open Admin Page
```
http://localhost/Etracker/backend/token_admin.html
```

### Step 2: Click "Run Migration"
This creates the `user_tokens` table in your database.

### Step 3: Done!
The system is now ready to use.

---

## Testing Multi-Device Login

### Test Scenario:

1. **Open Browser A** (Chrome)
   - Go to http://localhost/Etracker
   - Login with your account
   - ✅ You're logged in!

2. **Open Browser B** (Firefox or Incognito)
   - Go to http://localhost/Etracker
   - Login with THE SAME account
   - ✅ Both browsers are logged in!

3. **Logout from Browser A**
   - Click Logout in Browser A
   - ✅ Browser B is STILL logged in!

**Before:** Logging out Browser A would log out Browser B ❌  
**After:** Each browser has its own session ✅

---

## How It Works

### Old System (PHP Sessions):
```
User logs in → Creates ONE session
Login on another device → SAME session
Logout → Destroys the session → All devices logged out ❌
```

### New System (Tokens):
```
User logs in on Device A → Token #1 created
User logs in on Device B → Token #2 created
Logout Device A → Only Token #1 deleted
Device B still has Token #2 → Still logged in ✅
```

---

## Features

✅ **Multi-Device Support** - Login on 5 devices simultaneously  
✅ **Independent Logout** - Logout one device without affecting others  
✅ **30-Day Expiry** - Tokens last 30 days, then auto-cleanup  
✅ **Security** - HttpOnly cookies, device tracking, IP logging  
✅ **No HTTPS Required** - Works on localhost without SSL  

---

## Maintenance

### Option 1: Manual Cleanup (Via Browser)
```
http://localhost/Etracker/backend/token_admin.html
Click "Cleanup Tokens"
```

### Option 2: Automatic Cleanup (Recommended)

**Windows Task Scheduler:**
1. Search "Task Scheduler" in Start Menu
2. Create Basic Task
3. Trigger: Daily at 3:00 AM
4. Action: Start program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `E:\xampp\htdocs\Etracker\backend\cleanup_tokens.php`

This runs daily to remove expired tokens automatically.

---

## Troubleshooting

### "Still logs out all devices"

**Solution:** Clear browser cookies and login again. The old session cookies need to be replaced with new tokens.

### "Migration failed"

**Solution:** Check database connection in `backend/db.php` is correct.

### "Cookies not saving"

**Solution:** Make sure you're accessing via `localhost` not `127.0.0.1`. Browsers treat these differently for cookies.

---

## Need More Info?

See full documentation: `backend/TOKEN_SYSTEM_README.md`

---

## Summary

✅ **Installation:** 1 click (Run Migration)  
✅ **Configuration:** None needed (works out of the box)  
✅ **Testing:** 5 minutes (login on 2 browsers)  
✅ **Impact:** Zero breaking changes (backward compatible)  

**That's it!** Your system now supports multi-device logins. 🎉
