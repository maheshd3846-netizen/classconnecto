USE classconnecto;

-- Demo Student ID assuming '25B95A0703' exists from initial login
SET @student_id = IFNULL((SELECT id FROM users WHERE role='student' LIMIT 1), 1);
SET @admin_id = IFNULL((SELECT id FROM users WHERE role='admin' LIMIT 1), 1);

-- Notes
INSERT INTO notes (subject_id, title, unit_number, file_path) VALUES 
(3, 'Intro to Operating Systems', 1, '#'),
(3, 'Process Management & Scheduling', 2, '#'),
(3, 'Memory Management & Paging', 3, '#'),
(3, 'Deadlocks Explained', 4, '#'),
(4, 'Nielsen Design Heuristics', 1, '#'),
(4, 'Evaluation Techniques', 2, '#'),
(5, 'Dynamic Programming Intro', 1, '#');

-- Assignments
INSERT INTO assignments (subject_id, title, description, deadline) VALUES 
(3, 'Memory Management Simulation', 'Implement paging in C++', DATE_ADD(NOW(), INTERVAL 2 DAY)),
(3, 'Process Scheduling Calculations', 'Calculate turnaround time', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 'UI Evaluation Report', 'Evaluate apps using Nielsen heuristics', DATE_ADD(NOW(), INTERVAL 11 DAY)),
(5, 'Knapsack Problem Assignment', 'Real world applications', DATE_ADD(NOW(), INTERVAL 7 DAY));

-- Doubts
INSERT INTO doubts (subject_id, student_id, question, is_anonymous) VALUES 
(3, @student_id, 'Can someone explain differences between Long-term and Short-term scheduler simply?', 1),
(5, @student_id, 'Why is Dijkstra algorithm not working with negative weights?', 0);

-- Answers
INSERT INTO answers (doubt_id, user_id, answer, is_best_answer) VALUES 
(1, @admin_id, 'Long term brings processes to ready queue. Short term selects which to execute onto the CPU.', 1),
(2, @admin_id, 'Because once a node is processed, its distance is finalized. Negative edges can create shorter paths later.', 0);

-- Programs
INSERT INTO programs (subject_id, title, problem_statement, source_code, explanation, output_screenshot_path) VALUES 
(8, 'Binary Search Tree Implementation', 'Implement insertion and traversal algorithms for a BST.', '#include <iostream>\nusing namespace std;\n\nstruct Node {\n    int data;\n    Node* left;\n    Node* right;\n};\n\nint main() {\n    cout << "BST Code Executed" << endl;\n    return 0;\n}', 'BST node contains data, left, and right pointers.', '#'),
(8, 'Graph Traversal (BFS & DFS)', 'Implement BFS and DFS using adjacency list.', '#include <iostream>\n// Graph Code...', 'BFS uses a queue, DFS uses a stack or recursion.', '#');

-- Activity 
INSERT INTO student_activity (user_id, subject_id, activity_type, activity_value, created_at) VALUES 
(@student_id, 3, 'study_time_minutes', 45, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(@student_id, 4, 'study_time_minutes', 30, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(@student_id, 5, 'practice_program', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(@student_id, 3, 'view_note', 1, NOW()),
(@student_id, 3, 'complete_assignment', 1, DATE_SUB(NOW(), INTERVAL 5 DAY));
