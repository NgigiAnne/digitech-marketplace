<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Start session
session_start();

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $errors = [];
    
    $fullName = trim($data['fullName'] ?? '');
    $email = trim($data['newEmail'] ?? '');
    $password = $data['newPassword'] ?? '';
    $confirmPassword = $data['confirmPassword'] ?? '';
    
    // Validation checks
    if (empty($fullName)) {
        $errors['fullName'] = 'Full name is required';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Valid email is required';
    }
    
    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }
    
    if ($password !== $confirmPassword) {
        $errors['confirmPassword'] = 'Passwords do not match';
    }
    
    // Check if email already exists
    if (empty($errors['email'])) {
        $checkEmail = executeQuery("SELECT id FROM users WHERE email = ?", [$email]);
        if ($checkEmail->get_result()->num_rows > 0) {
            $errors['email'] = 'Email already registered';
        }
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        if (registerUser($fullName, $email, $password)) {
            // Automatically log in the user after registration
            if (loginUser($email, $password)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Registration successful!'
                ]);
                exit;
            }
        }
        
        // If we got here, something went wrong
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Registration failed. Please try again.'
        ]);
    } else {
        // Return validation errors
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $errors
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>