# Streamlined Registration System

This document explains the new streamlined registration system that allows users to register with just their school email address.

## Overview

Instead of filling out comprehensive forms with personal details, users now only need to enter their CVSU email address. The system automatically looks up their information from pre-populated master data tables and creates their account.

## Database Structure

### Master Students Table
```sql
CREATE TABLE master_students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    firstname VARCHAR(100) NOT NULL,
    middle_initial VARCHAR(10),
    lastname VARCHAR(100) NOT NULL,
    course VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Master Faculty Table
```sql
CREATE TABLE master_faculty (
    id INT PRIMARY KEY AUTO_INCREMENT,
    faculty_id VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    firstname VARCHAR(100) NOT NULL,
    middle_initial VARCHAR(10),
    lastname VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    position VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Setup Instructions

### 1. Create Master Tables
Run the migration script:
```bash
php run_master_tables_migration.php
```

### 2. Add Sample Data
Edit `setup_sample_data.php` and add your sample student/faculty data to the arrays, then run:
```bash
php setup_sample_data.php
```

### 3. Test the System
1. Go to `http://localhost/Etracker/register/`
2. Click "REGISTER"
3. Enter an email from your sample data
4. Complete password setup
5. Confirm your information
6. Complete OTP verification

## Registration Flow

1. **Email Input**: User enters CVSU email address
2. **Password Setup**: User creates password (with validation)
3. **Data Lookup**: System searches master tables for user info
4. **Confirmation**: User verifies their information is correct
5. **Account Creation**: System creates user account with looked-up data
6. **OTP Verification**: Email verification to confirm ownership
7. **Profile Setup**: User can add contact info and emergency contacts
8. **Complete**: User is logged in and redirected to dashboard

## Files Modified/Created

### New Files:
- `create_master_tables.sql` - Database schema for master tables
- `run_master_tables_migration.php` - Migration script
- `lookup_user.php` - API endpoint for user data lookup
- `setup_sample_data.php` - Script to populate sample data

### Modified Files:
- `index.html` - Updated registration form UI
- `register.php` - Added streamlined registration logic

## API Endpoints

### Lookup User Data
**Endpoint:** `POST /register/lookup_user.php`
**Payload:**
```json
{
  "email": "student@cvsu.edu.ph"
}
```
**Response:**
```json
{
  "status": "success",
  "role": "student|faculty",
  "user_info": {
    "student_id": "2021001",
    "firstname": "Juan",
    "middle_initial": "D",
    "lastname": "Dela Cruz",
    "course": "Bachelor of Science in Computer Science",
    "email": "student@cvsu.edu.ph"
  }
}
```

### Streamlined Registration
**Endpoint:** `POST /register/register.php`
**Payload:**
```json
{
  "email": "student@cvsu.edu.ph",
  "password": "user_password",
  "user_info": {...},
  "role": "student|faculty"
}
```

## Benefits

1. **Faster Registration**: 1 field instead of 10+ fields
2. **Data Accuracy**: Official school records prevent typos
3. **Security**: Email domain validation + OTP verification
4. **User Experience**: No tedious form filling
5. **Scalability**: Easy to import entire student/faculty databases
6. **Consistency**: Standardized data across the system

## Future Enhancements

1. **CSV Import**: Bulk import from school database exports
2. **Admin Interface**: Web interface for managing master data
3. **Data Synchronization**: Automatic sync with school systems
4. **Role-based Access**: Different registration flows for different user types
5. **Audit Logging**: Track registration attempts and data changes

## Troubleshooting

### Common Issues:

1. **"Email not found in records"**
   - Check if email exists in master_students or master_faculty tables
   - Verify email format and domain (@cvsu.edu.ph)

2. **"Email already registered"**
   - User has already completed registration
   - Check users table for existing accounts

3. **Migration fails**
   - Check database permissions
   - Ensure tables don't already exist with different structure

### Debug Commands:
```bash
# Check master tables
php check_master_tables.php

# View sample data
mysql -u username -p etracker_db -e "SELECT * FROM master_students LIMIT 5;"
mysql -u username -p etracker_db -e "SELECT * FROM master_faculty LIMIT 5;"
```

## Security Considerations

1. **Email Domain Validation**: Only @cvsu.edu.ph emails allowed
2. **Password Requirements**: Strong password policy enforced
3. **OTP Verification**: Email ownership confirmation required
4. **Data Sanitization**: All inputs validated and sanitized
5. **SQL Injection Protection**: Prepared statements used throughout

## Support

For issues with the streamlined registration system:
1. Check this README for common solutions
2. Review error logs in the browser console
3. Verify database connectivity and table structure
4. Test with the provided sample data first