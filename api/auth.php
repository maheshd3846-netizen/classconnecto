<?php
session_start();
header('Content-Type: application/json');

require_once '../db/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reg_number = $_POST['reg_number'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($reg_number) || empty($password)) {
         echo json_encode(['success' => false, 'message' => 'Please fill all fields']);
         exit;
    }

    // For demo purposes, we will allow "ADMIN" "admin" and create it if it doesn't exist
    // Otherwise we check DB
    try {
        $stmt = $pdo->prepare("SELECT id, register_number, password, role, full_name FROM users WHERE register_number = :reg LIMIT 1");
        $stmt->execute(['reg' => $reg_number]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['password']) || $password === $user['password'] || ($reg_number === 'ADMIN' && $password === 'admin')) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['reg_number'] = $user['register_number'];
                
                echo json_encode(['success' => true]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
                exit;
            }
        } else {
             echo json_encode(['success' => false, 'message' => 'User not found. Please register first.']);
             exit;
        }
    } catch (Exception $e) {
         echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
         exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
