-- Sample data for eTracker database
-- Run this after creating the tables

-- Insert departments
INSERT INTO departments (department_name, department_code) VALUES
('Department of Hospitality Management', 'DHM'),
('Department of Language and Mass Communication', 'DLMC'),
('Department of Physical Education', 'DPE'),
('Department of Social Sciences and Humanities', 'DSSH'),
('Teacher Education Department', 'TED'),
('Department of Administration - ENTREP', 'DA-ENTREP'),
('Department of Administration - BSOA', 'DA-BSOA'),
('Department of Administration - BM', 'DA-BM'),
('Department of Computer Studies', 'DCS');

-- Insert sample users
INSERT INTO users (firstname, lastname, email, phone, department, role, comm_preference, verification_status) VALUES
('John', 'Doe', 'john.doe@cvsu.edu.ph', '09123456789', 'Computer Science', 'faculty', 'email', 'verified'),
('Jane', 'Smith', 'jane.smith@cvsu.edu.ph', '09123456790', 'Engineering', 'faculty', 'email', 'verified'),
('Admin', 'User', 'admin@cvsu.edu.ph', '09123456791', 'Administration', 'admin', 'email', 'verified'),
('Student', 'One', 'student1@cvsu.edu.ph', '09123456792', 'Computer Science', 'student', 'email', 'verified');

-- Insert faculty
INSERT INTO faculty (user_id, faculty_name, faculty_id, department) VALUES
(1, 'John Doe', 'FAC001', 'Computer Science'),
(2, 'Jane Smith', 'FAC002', 'Engineering');

-- Insert sample programs
INSERT INTO programs (program_name, project_titles, department, location, start_date, end_date, status, faculty_id, description, max_students) VALUES
('Community Health Program', '["Health Education Workshop", "Medical Mission", "Nutrition Awareness Campaign"]', 'Health Sciences', 'Barangay San Jose', '2024-01-15', '2024-12-15', 'ended', 1, 'A comprehensive health program for the community', 50),
('Digital Literacy Training', '["Basic Computer Skills", "Internet Safety Workshop", "Digital Tools for Seniors"]', 'Computer Science', 'Community Center', '2024-03-01', '2024-11-30', 'ended', 2, 'Training program to improve digital literacy in the community', 30),
('Environmental Conservation Project', '["Tree Planting Activity", "Waste Management Workshop", "Clean River Campaign"]', 'Environmental Science', 'Various Locations', '2024-02-01', '2024-10-31', 'ongoing', 1, 'Environmental awareness and conservation activities', 40),
('Youth Leadership Development', '["Leadership Workshop", "Community Service Project", "Mentorship Program"]', 'Social Sciences', 'School Campus', '2024-04-01', '2024-12-31', 'ongoing', 2, 'Developing leadership skills among youth', 25),
('Agricultural Innovation Program', '["Modern Farming Techniques", "Sustainable Agriculture Workshop", "Farmers Training"]', 'Agriculture', 'Rural Areas', '2024-05-01', '2024-11-30', 'planning', 1, 'Introducing innovative agricultural practices', 35),
('Cultural Heritage Preservation', '["Cultural Documentation", "Heritage Festival", "Traditional Arts Workshop"]', 'Arts and Culture', 'Cultural Center', '2024-06-01', '2024-12-15', 'planning', 2, 'Preserving and promoting local cultural heritage', 45),
('STEM Education Initiative', '["Science Fair", "Technology Workshop", "Mathematics Competition"]', 'Education', 'Multiple Schools', '2024-07-01', '2024-12-31', 'ongoing', 1, 'Promoting STEM education in schools', 60),
('Disaster Preparedness Training', '["Emergency Response Drill", "First Aid Training", "Evacuation Planning"]', 'Civil Defense', 'Community Halls', '2024-08-01', '2024-10-31', 'ended', 2, 'Preparing communities for disaster response', 50);

-- Insert sample participants
INSERT INTO participants (program_id, student_name, student_email, status) VALUES
(1, 'Maria Santos', 'maria.santos@email.com', 'accepted'),
(1, 'Juan Dela Cruz', 'juan.delacruz@email.com', 'accepted'),
(2, 'Ana Garcia', 'ana.garcia@email.com', 'accepted'),
(2, 'Pedro Martinez', 'pedro.martinez@email.com', 'accepted'),
(3, 'Rosa Reyes', 'rosa.reyes@email.com', 'pending'),
(3, 'Carlos Lopez', 'carlos.lopez@email.com', 'accepted');

-- Insert sample sessions
INSERT INTO sessions (program_id, session_title, session_date, session_start, session_end, location) VALUES
(1, 'Health Education Workshop', '2024-01-20', '09:00:00', '12:00:00', 'Community Center'),
(1, 'Medical Mission', '2024-02-15', '08:00:00', '17:00:00', 'Barangay Health Center'),
(2, 'Basic Computer Skills', '2024-03-05', '10:00:00', '14:00:00', 'Computer Lab'),
(2, 'Internet Safety Workshop', '2024-03-20', '13:00:00', '16:00:00', 'Library'),
(3, 'Tree Planting Activity', '2024-02-10', '07:00:00', '11:00:00', 'City Park'),
(3, 'Waste Management Workshop', '2024-03-01', '14:00:00', '17:00:00', 'Town Hall');