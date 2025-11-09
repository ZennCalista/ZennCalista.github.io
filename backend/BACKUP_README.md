# Database Backup and Cleanup Scripts

## Overview
These scripts allow you to backup the entire eTracker database and optionally delete data from specific tables.

## Files Created
1. **backup_and_cleanup.php** - Main script for creating backups and deleting data
2. **download_backup.php** - Helper script for downloading backup files
3. **list_backups.php** - View all existing backup files

## Security
- All scripts only work from localhost (127.0.0.1)
- Cannot be accessed from external IPs

## Usage

### 1. Backup Only (No Deletion)
Navigate to: `http://localhost/Etracker/backend/backup_and_cleanup.php`

This will:
- Create a complete SQL dump of the database
- Save it to `backend/backups/` directory
- Show download link
- NOT delete any data

### 2. Backup AND Delete Data
Navigate to: `http://localhost/Etracker/backend/backup_and_cleanup.php?delete=1`

This will:
- Create a complete SQL dump of the database
- Save it to `backend/backups/` directory
- Show download link
- Delete all data from these tables:
  - images
  - images_archive
  - programs
  - programs_archive
- Show results table with rows deleted

### 3. View All Backups
Navigate to: `http://localhost/Etracker/backend/list_backups.php`

This will show:
- List of all backup files
- File size
- Creation date
- Download button for each backup

## Backup File Format
Backup files are named: `etracker_backup_YYYY-MM-DD_HH-MM-SS.sql`

Example: `etracker_backup_2025-11-09_19-30-45.sql`

## Tables Affected by Cleanup
The cleanup function deletes data from:
- **images** - Program images
- **images_archive** - Archived program images
- **programs** - Active programs
- **programs_archive** - Archived programs

## Important Notes
1. **Always create a backup before deleting data**
2. The backup is created BEFORE deletion, so you can restore if needed
3. Backups are stored locally in `backend/backups/` directory
4. Deletion is PERMANENT and cannot be undone without restoring from backup
5. A confirmation popup appears before deletion (when using web interface)

## Restoring from Backup
To restore a backup:
1. Download the backup file
2. Use phpMyAdmin or MySQL command line
3. Import the SQL file into your database

OR use command line:
```bash
mysql -h database-1.ch0e2sa0mf0l.ap-southeast-2.rds.amazonaws.com -u admin -p etracker < backup_file.sql
```

## Directory Structure
```
backend/
 backup_and_cleanup.php  (Main script)
 download_backup.php     (Download helper)
 list_backups.php        (List backups)
 backups/                (Created automatically)
    etracker_backup_2025-11-09_19-30-45.sql
    ...
 BACKUP_README.md        (This file)
```
