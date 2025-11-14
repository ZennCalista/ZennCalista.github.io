-- Program Images Table Setup
-- Run this script to create the necessary table for program photo uploads

-- Create program_images table
CREATE TABLE IF NOT EXISTS program_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    image_data MEDIUMBLOB NOT NULL,
    image_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
    INDEX idx_program_id (program_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;