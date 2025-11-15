-- Add email_verified column to users table
ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT FALSE NOT NULL;

-- Update existing users to have email_verified = true (assuming they were created before OTP system)
-- Uncomment the line below if you want to mark all existing users as verified
-- UPDATE users SET email_verified = TRUE WHERE email_verified = FALSE;