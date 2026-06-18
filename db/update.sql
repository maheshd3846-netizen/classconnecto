USE classconnecto;

-- Update the ENUM for roles
ALTER TABLE users MODIFY COLUMN role ENUM('student', 'admin', 'faculty', 'cr') DEFAULT 'student';

-- Create reference_links if not exists
CREATE TABLE IF NOT EXISTS reference_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    url VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Insert a demo Faculty and CR
INSERT IGNORE INTO users (register_number, password, role, full_name) VALUES 
('FACULTY1', 'faculty', 'faculty', 'Dr. Smith'),
('CR_STUDENT', 'cr', 'cr', 'Class Rep Jane');
