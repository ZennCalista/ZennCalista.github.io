-- Create master data tables for streamlined registration
-- These tables contain official school records that are pre-populated

-- Master students table (official student records)
CREATE TABLE IF NOT EXISTS master_students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    firstname VARCHAR(100) NOT NULL,
    middle_initial VARCHAR(10),
    lastname VARCHAR(100) NOT NULL,
    course VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Master faculty table (official faculty records)
CREATE TABLE IF NOT EXISTS master_faculty (
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

-- Add indexes for faster lookups
CREATE INDEX idx_master_students_email ON master_students(email);
CREATE INDEX idx_master_faculty_email ON master_faculty(email);