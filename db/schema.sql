-- ClassConnecto Database Schema

CREATE DATABASE IF NOT EXISTS classconnecto;
USE classconnecto;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    register_number VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin', 'faculty', 'cr') DEFAULT 'student',
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL,
    type ENUM('theory', 'lab') DEFAULT 'theory',
    theory_subject_id INT NULL, -- For lab subjects to link back to theory
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (theory_subject_id) REFERENCES subjects(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    unit_number INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    extracted_text TEXT,
    ai_summary TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    deadline DATETIME NOT NULL,
    file_path VARCHAR(255) DEFAULT '#',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reference_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    url VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS doubts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    student_id INT NOT NULL,
    question TEXT NOT NULL,
    is_anonymous BOOLEAN DEFAULT TRUE,
    ai_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doubt_id INT NOT NULL,
    user_id INT NOT NULL,
    answer TEXT NOT NULL,
    upvotes INT DEFAULT 0,
    is_best_answer BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doubt_id) REFERENCES doubts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    problem_statement TEXT,
    source_code TEXT NOT NULL,
    explanation TEXT,
    output_screenshot_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS student_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_id INT NOT NULL,
    activity_type ENUM('view_note', 'complete_assignment', 'practice_program', 'ask_doubt', 'study_time_minutes') NOT NULL,
    activity_value INT DEFAULT 1, -- e.g., minutes studied, or just 1 for a count
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Insert dummy subjects
INSERT INTO subjects (name, code, type) VALUES ('Managerial Economics and Financial Analysis', 'MEFA', 'theory');
INSERT INTO subjects (name, code, type) VALUES ('Probability and Statistics', 'P&S', 'theory');
INSERT INTO subjects (name, code, type) VALUES ('Operating Systems', 'OS', 'theory');
INSERT INTO subjects (name, code, type) VALUES ('Human Computer Interaction', 'HCI', 'theory');
INSERT INTO subjects (name, code, type) VALUES ('Advanced Data Structures and Algorithms', 'ADSA', 'theory');
INSERT INTO subjects (name, code, type) VALUES ('Embedded Systems', 'ES', 'theory');

-- Retrieve ID for HCI & ADSA for lab linking (assuming IDs will be 4 and 5 sequentially)
INSERT INTO subjects (name, code, type, theory_subject_id) VALUES ('HCI Lab', 'HCIL', 'lab', 4);
INSERT INTO subjects (name, code, type, theory_subject_id) VALUES ('ADSA Lab', 'ADSAL', 'lab', 5);
INSERT INTO subjects (name, code, type) VALUES ('DTI Lab', 'DTIL', 'lab');
INSERT INTO subjects (name, code, type) VALUES ('FSD Lab', 'FSDL', 'lab');

-- Insert a default admin for testing (Register Number: ADMIN, Password: admin)
-- In a real scenario use hashed passwords, for now we will use plain text for MVP
INSERT IGNORE INTO users (register_number, password, role, full_name) VALUES ('ADMIN', 'password_hash_here', 'admin', 'System Admin');

-- Insert dummy assignments
INSERT INTO assignments (subject_id, title, description, deadline) VALUES 
(3, 'Memory Management Writeup', 'Explain internal and external fragmentation with examples.', DATE_ADD(NOW(), INTERVAL 2 DAY)),
(5, 'Dynamic Programming Real-world Example', 'Find a novel application of Knapsack problem.', DATE_ADD(NOW(), INTERVAL 11 DAY)),
(4, 'UI Heuristics Evaluation', 'Evaluate 2 popular apps against Nielsen''s heuristics.', DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Insert dummy programs
INSERT INTO programs (subject_id, title, problem_statement, source_code, explanation) VALUES 
(7, 'Program to Reverse an Array', 'Write a C++ program to reverse an array of N integers without using an extra array.', '#include <iostream>\nusing namespace std;\n\nint main() {\n    int n;\n    cout << "Enter n: ";\n    cin >> n;\n    int arr[n];\n    for(int i=0; i<n; i++) cin >> arr[i];\n    for(int i=0; i<n/2; i++) swap(arr[i], arr[n-1-i]);\n    for(int i=0; i<n; i++) cout << arr[i] << " ";\n    return 0;\n}', 'We iterate up to n/2 and swap the i-th element with the (n-1-i)-th element. This reverses the array in-place with O(1) extra space.');

-- Insert dummy notes
INSERT INTO notes (subject_id, title, unit_number, file_path) VALUES 
(3, 'Introduction to OS', 1, '#'),
(5, 'Trees and Graphs Basics', 2, '#');
