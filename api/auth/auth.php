<?php
require_once 'config.php';

function registerUser($fullName, $email, $password) {
    global $conn;
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $sql = "INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $fullName, $email, $hashedPassword);
        $stmt->execute();
        
        return $stmt->affected_rows === 1;
    } catch (mysqli_sql_exception $e) {
        // Log the error (in a real application)
        error_log("Registration error: " . $e->getMessage());
        return false;
    }
}

function loginUser($email, $password) {
    global $conn;
    
    $sql = "SELECT id, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $email;
            $_SESSION['logged_in'] = true;
            
            return true;
        }
    }
    return false;
}


function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}
?>