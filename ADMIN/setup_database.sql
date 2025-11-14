-- Complete database schema for eTracker system
-- This matches the AWS RDS database structure and supports all modal fields

-- Create programs table with all required fields
CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_name VARCHAR(255) NOT NULL,
    project_titles LONGTEXT NULL,
    department_id INT NULL,
    department VARCHAR(100) NULL,
    program_type VARCHAR(100) NULL,
    location VARCHAR(255) NULL,
    target_audience VARCHAR(100) NULL,
    start_date DATE NULL,
    previous_date DATE NULL,
    end_date DATE NULL,
    status ENUM('planning','ongoing','ended','completed','cancelled') NULL DEFAULT 'planning',
    max_students INT NULL,
    male_count INT NULL DEFAULT 0,
    female_count INT NULL DEFAULT 0,
    description TEXT NULL,
    requirements TEXT NULL,
    budget DECIMAL(10,2) NULL,
    sdg_goals TEXT NULL,
    faculty_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    archived_at DATETIME NULL
);

-- Create program_sessions table (not just 'sessions')
CREATE TABLE IF NOT EXISTS program_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    session_title VARCHAR(255) NOT NULL,
    session_date DATE NOT NULL,
    session_start TIME NOT NULL,
    session_end TIME NOT NULL,
    location VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
);

-- Create faculty table with complete structure
CREATE TABLE IF NOT EXISTS faculty (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    department VARCHAR(100) NULL,
    position VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    faculty_name VARCHAR(100) NULL,
    faculty_id VARCHAR(50) NULL
);

-- Create users table with complete structure
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(100) NULL,
    lastname VARCHAR(100) NULL,
    email VARCHAR(255) NULL,
    password VARCHAR(255) NULL,
    department_id INT NULL,
    role ENUM('admin','faculty','student') NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    phone VARCHAR(20) NULL,
    department VARCHAR(100) NULL,
    comm_preference ENUM('email','sms') NULL DEFAULT 'email',
    verification_status ENUM('verified','unverified') NULL DEFAULT 'unverified'
);

-- Create departments table
CREATE TABLE IF NOT EXISTS departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(255) NOT NULL
);

-- Create participants table
CREATE TABLE IF NOT EXISTS participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NULL,
    user_id INT NULL,
    status ENUM('pending','accepted','rejected') NULL DEFAULT 'pending',
    enrolled_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
);

-- Insert sample data if tables are empty

-- Sample departments
INSERT IGNORE INTO departments (department_id, department_name) VALUES
(1, 'Department of Hospitality Management'),
(2, 'Department of Language and Mass Communication'),
(3, 'Department of Physical Education'),
(4, 'Department of Social Sciences and Humanities'),
(5, 'Teacher Education Department'),
(6, 'Department of Administration - ENTREP'),
(7, 'Department of Administration - BSOA'),
(8, 'Department of Administration - BM'),
(9, 'Department of Computer Studies');

-- Sample users
INSERT IGNORE INTO users (id, firstname, lastname, email, role, department, phone, comm_preference, verification_status) VALUES
(1, 'Admin', 'User', 'admin@cvsu.edu.ph', 'admin', 'Administration', '09123456791', 'email', 'verified'),
(2, 'John', 'Doe', 'john.doe@cvsu.edu.ph', 'faculty', 'Computer Science', '09123456789', 'email', 'verified'),
(3, 'Jane', 'Smith', 'jane.smith@cvsu.edu.ph', 'faculty', 'Engineering', '09123456790', 'email', 'verified');

-- Sample faculty
INSERT IGNORE INTO faculty (id, user_id, department, position, faculty_name, faculty_id) VALUES
(1, 2, 'Computer Science', 'Professor', 'John Doe', 'FAC001'),
(2, 3, 'Engineering', 'Associate Professor', 'Jane Smith', 'FAC002');

-- Sample programs with all fields
INSERT IGNORE INTO programs (id, program_name, project_titles, department_id, department, program_type, location, target_audience, start_date, end_date, status, max_students, male_count, female_count, description, requirements, budget, sdg_goals, faculty_id) VALUES
(1, 'Community Health Program',
 '["Health Education Workshop", "Medical Mission", "Nutrition Awareness Campaign"]',
 1, 'Department of Hospitality Management', 'Extension Program', 'Barangay San Jose', 'Community Members',
 '2024-01-15', '2024-12-15', 'ended', 50, 20, 30,
 'A comprehensive health program for the community',
 'Basic health knowledge, willingness to participate in community activities',
 15000.00, '[1,2,3]', 1),
(2, 'Digital Literacy Training',
 '["Basic Computer Skills", "Internet Safety Workshop", "Digital Tools for Seniors"]',
 9, 'Department of Computer Studies', 'Training Workshop', 'Community Center', 'Students & Faculty',
 '2024-03-01', '2024-11-30', 'ended', 30, 15, 15,
 'Training program to improve digital literacy in the community',
 'Basic reading skills, interest in technology',
 8000.00, '[4,9]', 2),
(3, 'Environmental Conservation Project',
 '["Tree Planting Activity", "Waste Management Workshop", "Clean River Campaign"]',
 4, 'Department of Social Sciences and Humanities', 'Community Service', 'Various Locations', 'Mixed Audience',
 '2024-02-01', '2024-10-31', 'ongoing', 40, 18, 22,
 'Environmental awareness and conservation activities',
 'Physical fitness for outdoor activities',
 12000.00, '[6,12,13]', 1);

-- Sample program sessions
INSERT IGNORE INTO program_sessions (program_id, session_title, session_date, session_start, session_end, location) VALUES
(1, 'Health Education Workshop', '2024-01-20', '09:00:00', '12:00:00', 'Community Center'),
(1, 'Medical Mission', '2024-02-15', '08:00:00', '17:00:00', 'Barangay Health Center'),
(2, 'Basic Computer Skills', '2024-03-05', '10:00:00', '14:00:00', 'Computer Lab'),
(2, 'Internet Safety Workshop', '2024-03-20', '13:00:00', '16:00:00', 'Library'),
(3, 'Tree Planting Activity', '2024-02-10', '07:00:00', '11:00:00', 'City Park'),
(3, 'Waste Management Workshop', '2024-03-01', '14:00:00', '17:00:00', 'Town Hall');

-- Sample participants
INSERT IGNORE INTO participants (program_id, user_id, status) VALUES
(1, 3, 'accepted'),
(2, 3, 'accepted'),
(3, 2, 'accepted');
