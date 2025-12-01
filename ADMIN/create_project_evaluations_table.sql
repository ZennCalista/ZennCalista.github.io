-- Create project_evaluations table to store evaluation data
-- Note: project_id is VARCHAR to support composite IDs like 'prog_123', 'projt_123_1', etc.
-- No foreign key constraints as we evaluate multiple item types (programs, projects, standalone)

-- Drop existing foreign key constraint if it exists
ALTER TABLE project_evaluations DROP FOREIGN KEY IF EXISTS project_evaluations_ibfk_1;

CREATE TABLE IF NOT EXISTS project_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id VARCHAR(50) NOT NULL COMMENT 'Composite ID: prog_X, projt_X_Y, or standalone_X',
    impact_rating INT,
    quality_rating INT,
    sustainability_rating INT,
    innovation_rating INT,
    collaboration_rating INT,
    budget_efficiency INT,
    timeliness_rating INT,
    overall_rating DECIMAL(3,2),
    evaluation_comments TEXT,
    recommendations TEXT,
    evaluation_status VARCHAR(50),
    evaluation_date DATETIME,
    evaluator_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_project_id (project_id),
    INDEX idx_evaluator_id (evaluator_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;