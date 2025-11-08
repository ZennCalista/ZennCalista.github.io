-- Coordinators Table Setup
-- Run this script to create the necessary tables for the coordinators feature

-- Drop tables if they exist (for clean reinstall)
DROP TABLE IF EXISTS coordinator_images;
DROP TABLE IF EXISTS coordinators;

-- Create coordinators table
CREATE TABLE coordinators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    department VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    office_location VARCHAR(255) NULL,
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create coordinator_images table
CREATE TABLE coordinator_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coordinator_id INT NOT NULL,
    image_data MEDIUMBLOB NOT NULL,
    image_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coordinator_id) REFERENCES coordinators(id) ON DELETE CASCADE,
    INDEX idx_coordinator_id (coordinator_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default placeholder coordinators
INSERT INTO coordinators (name, department, display_order) VALUES
('Dr. Maria Santos', 'Department of Computer Studies', 1),
('Prof. Juan Dela Cruz', 'Department of Biological and Physical Sciences', 2),
('Dr. Ana Reyes', 'Department of Management', 3),
('Prof. Carlos Garcia', 'Department of Hospitality Management', 4),
('Dr. Sofia Martinez', 'Department of Languages and Mass Communication', 5),
('Prof. Miguel Torres', 'Department of Physical Education', 6),
('Dr. Isabel Fernandez', 'Department of Social Sciences and Humanities', 7),
('Prof. Ramon Villanueva', 'Teacher Education Department', 8),
('Dr. Carmen Lopez', 'Department of Computer Studies', 9),
('Prof. Eduardo Ramos', 'Department of Management', 10);

-- Success message
SELECT 'Coordinators tables created successfully!' AS message;
