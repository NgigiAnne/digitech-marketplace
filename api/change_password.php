<?php
require_once __DIR__ . '/../includes/database.php'; // Your MySQLi connection file
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Only logged-in users can change passwords
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get current user ID from session
$userId = $_SESSION['user_id'];

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($data['current_password']) || empty($data['new_password']) || empty($data['confirm_password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

if ($data['new_password'] !== $data['confirm_password']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
    exit();
}

if (strlen($data['new_password']) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
    exit();
}

try {
    // Get current password hash from database
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Verify current password
    if (!password_verify($data['current_password'], $user['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit();
    }
    
    // Check if new password is different
    if (password_verify($data['new_password'], $user['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'New password must be different from current password']);
        exit();
    }
    
    // Hash new password
    $newPasswordHash = password_hash($data['new_password'], PASSWORD_DEFAULT);
    
    // Update password in database
    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $updateStmt->bind_param("si", $newPasswordHash, $userId);
    $updateStmt->execute();
    
    if ($updateStmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    }
    
    $updateStmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}