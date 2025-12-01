-- Add certificate-related columns to participants table
ALTER TABLE participants 
ADD COLUMN certificate_issued TINYINT(1) DEFAULT 0 AFTER status,
ADD COLUMN issued_on DATETIME DEFAULT NULL AFTER certificate_issued,
ADD COLUMN evaluated TINYINT(1) DEFAULT 0 AFTER issued_on,
ADD COLUMN certificate_file VARCHAR(255) DEFAULT NULL AFTER evaluated;