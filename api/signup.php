<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Enable detailed error reporting


require_once __DIR__ . '/../config/db_connect.php';

$response = ['success' => false, 'message' => ''];

try {
    // Verify request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method', 405);
    }

    // Get and validate JSON input
    $jsonInput = file_get_contents('php://input');
    if (empty($jsonInput)) {
        throw new Exception('No input data received', 400);
    }

    $data = json_decode($jsonInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg(), 400);
    }

    // Validate required fields
    $requiredFields = [
        'fullName' => 'Full name is required',
        'email' => 'Email is required',
        'newpassword' => 'Password is required',
        'confirmPassword' => 'Password confirmation is required'
    ];

    foreach ($requiredFields as $field => $errorMsg) {
        if (empty($data[$field])) {
            throw new Exception($errorMsg, 400);
        }
    }

    // Additional validations
    if ($data['newpassword'] !== $data['confirmPassword']) {
        throw new Exception('Passwords do not match', 400);
    }

    if (strlen($data['newpassword']) < 8) {
        throw new Exception('Password must be at least 8 characters', 400);
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format', 400);
    }

    // Check if email exists
    $stmt = $pdo->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->rowCount() > 0) {
        throw new Exception('Email already registered', 409);
    }

    // Hash password and create user
    $hashedPassword = password_hash($data['newpassword'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)");
    
    if (!$stmt->execute([$data['fullName'], $data['email'], $hashedPassword])) {
        throw new Exception('Failed to create user account', 500);
    }

    // Success response
    $response = [
        'success' => true,
        'message' => 'Registration successful!',
        'user' => [
            'id' => $pdo->lastInsertId(),
            'email' => $data['email']
        ]
    ];

    http_response_code(201);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    $response['message'] = $e->getMessage();
    
    // Log detailed error for debugging
    error_log("Registration Error: " . $e->getMessage() . "\n" . 
             "Request Data: " . print_r($data ?? [], true) . "\n" .
             "Trace: " . $e->getTraceAsString());
}

echo json_encode($response);