# Database Migration Guide

## Issue Fixed
This migration resolves the following errors in Document.html:
- ❌ `Unknown column 'admin_remarks' in 'field list'`
- ❌ `500 Internal Server Error` when approving/rejecting documents

## What This Migration Does
Adds three new columns to the `document_uploads` table:
1. **admin_remarks** (TEXT) - Stores admin review comments
2. **reviewed_by** (INT) - Foreign key to users table, tracks which admin reviewed
3. **reviewed_at** (DATETIME) - Timestamp of when the review happened

## How to Run the Migration

### Option 1: Web Interface (Recommended)
1. Navigate to: `https://etracker-portal.me/backend/migrations/run_migration.html`
2. Click the "▶️ Run Migration" button
3. Wait for the success message

### Option 2: Direct PHP Execution
```bash
php backend/migrations/add_document_review_columns.php
```

### Option 3: Command Line
```bash
cd E:\xampp\htdocs\Etracker
php backend/migrations/add_document_review_columns.php
```

## Verification
After running the migration, the document review functionality should work without errors.

## Safety
- ✅ The migration checks if columns already exist before adding them
- ✅ Safe to run multiple times
- ✅ Will not delete or modify existing data
- ✅ Only adds new columns with NULL default values

## Rollback (if needed)
If you need to remove these columns:
```sql
ALTER TABLE document_uploads 
DROP COLUMN admin_remarks,
DROP COLUMN reviewed_by,
DROP COLUMN reviewed_at;
```

---
**Note:** Make sure you're logged in as admin before running the web interface migration.