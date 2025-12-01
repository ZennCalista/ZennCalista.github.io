-- Add faculty certificate issued_on column to programs table
ALTER TABLE programs 
ADD COLUMN faculty_certificate_issued_on DATETIME DEFAULT NULL;