-- Update programs table to add missing columns for faculty program creation
-- Run this if you get "Unknown column" errors when creating programs

-- Add columns one by one to avoid syntax issues
ALTER TABLE programs ADD COLUMN program_level VARCHAR(50) NULL DEFAULT 'beginner';
ALTER TABLE programs ADD COLUMN program_category VARCHAR(100) NULL DEFAULT 'extension';
ALTER TABLE programs ADD COLUMN sessions_data LONGTEXT NULL;
ALTER TABLE programs ADD COLUMN dept_approval ENUM('pending','approved','rejected') NULL DEFAULT 'pending';
ALTER TABLE programs ADD COLUMN priority ENUM('low','normal','high') NULL DEFAULT 'normal';
ALTER TABLE programs ADD COLUMN user_id INT NULL;
ALTER TABLE programs ADD COLUMN faculty_certificate_issued TINYINT(1) NOT NULL DEFAULT 0;