<?php
session_start();
header('Content-Type: application/json');

require_once '../db/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $reg_number = trim($_POST['reg_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $role = $_POST['role'] ?? 'student';

    if (empty($full_name) || empty($reg_number) || empty($password) || empty($confirm_password)) {
         echo json_encode(['success' => false, 'message' => 'Please fill all fields']);
         exit;
    }

    $allowed_roles = ['student', 'faculty', 'cr'];
    if (!in_array($role, $allowed_roles)) {
         echo json_encode(['success' => false, 'message' => 'Invalid role selected']);
         exit;
    }

    if ($password !== $confirm_password) {
         echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
         exit;
    }

    if (strlen($password) < 6) {
         echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
         exit;
    }

    try {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE register_number = :reg LIMIT 1");
        $stmt->execute(['reg' => $reg_number]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Register number/ID already exists']);
            exit;
        }

        // Hash password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (register_number, password, role, full_name) VALUES (:reg, :pass, :role, :name)");
        $stmt->execute([
            'reg' => $reg_number,
            'pass' => $hashed_password,
            'role' => $role,
            'name' => $full_name
        ]);
        
        $new_id = $pdo->lastInsertId();
        
        // Auto-login after successful registration
        $_SESSION['user_id'] = $new_id;
        $_SESSION['role'] = $role;
        $_SESSION['full_name'] = $full_name;
        $_SESSION['reg_number'] = $reg_number;
        
        echo json_encode(['success' => true]);
        exit;

    } catch (Exception $e) {
         echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
         exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
