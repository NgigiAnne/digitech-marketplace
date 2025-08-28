<?php
session_start();
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
$conn = getDB();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Set base path for redirects
$base_path = '/Digitech/'; // Change this to your project folder name

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // CSRF protection - validate token first
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        // Regenerate token before redirect
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header("Location: {$base_path}index.php?error=csrf_failed");
        exit();
    }

    try {
        // Verify database connection
        if ($conn->connect_error) {
            throw new Exception('db_error');
        }

        if ($action === 'signup') {
            // [Existing signup code remains exactly the same]
            // ... (keep all your current signup logic)
            
            // Auto-login after signup
            $user_id = $stmt->insert_id;
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'user';
            
            // Update last login for new user
            $update_login = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $update_login->bind_param("i", $user_id);
            $update_login->execute();
            $update_login->close();
            
            // Regenerate token before redirect
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header("Location: {$base_path}user/dashboard.php?success=signup");
            exit();
            
        } elseif ($action === 'login') {
            $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
            $password = $_POST['password'] ?? '';
            $remember_me = isset($_POST['remember_me']);
            
            if (empty($email) || empty($password)) {
                throw new Exception('invalid_input');
            }
            
            // Get user with proper error handling
            $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            if (!$stmt) {
                throw new Exception('db_error');
            }
            
            $stmt->bind_param("s", $email);
            if (!$stmt->execute()) {
                throw new Exception('db_error');
            }
            
            $result = $stmt->get_result();
            
            // Check if user exists first
            if ($result->num_rows === 0) {
                throw new Exception('invalid_credentials');
            }
            
            $user = $result->fetch_assoc();
            
            // Verify $user exists and has password field
            if (!$user || !isset($user['password'])) {
                throw new Exception('db_error');
            }
            
            // Verify password against hash
            if (!password_verify($password, $user['password'])) {
                throw new Exception('invalid_credentials');
            }
            
            // Check if password needs rehashing
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $new_hash, $user['id']);
                $update_stmt->execute();
                $update_stmt->close();
            }
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Update last login time
            $update_login = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            if (!$update_login) {
                error_log("Failed to prepare last_login update: ".$conn->error);
            } else {
                $update_login->bind_param("i", $user['id']);
                if (!$update_login->execute()) {
                    error_log("Failed to update last_login: ".$update_login->error);
                }
                $update_login->close();
            }
            
            // Remember me functionality
            if ($remember_me) {
                $token = bin2hex(random_bytes(32));
                $hashed_token = hash('sha256', $token);
                $expiry = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60);
                
                // Store token in database
                $stmt = $conn->prepare("INSERT INTO auth_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user['id'], $hashed_token, $expiry);
                $stmt->execute();
                $stmt->close();
                
                // Set secure cookie
                setcookie('remember_me', $token, [
                    'expires' => time() + 30 * 24 * 60 * 60,
                    'path' => '/',
                    'domain' => '',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
            }
            
            // Regenerate token before redirect
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $dashboard_path = ($user['role'] === 'admin') ? 'admin' : 'user';
            header("Location: {$base_path}{$dashboard_path}/dashboard.php");
            exit();
            
        } elseif ($action === 'change_password') {
            // [Existing change_password code remains exactly the same]
            // ... (keep all your current password change logic)
        }
    } catch (Exception $e) {
        // Regenerate token on errors
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        error_log('Authentication error: ' . $e->getMessage());
        $error_param = urlencode($e->getMessage());
        $redirect_url = $base_path . 'index.php?' . (isset($_GET['signup']) ? 'signup=1&' : '') . 'error=' . $error_param;
        header("Location: $redirect_url");
        exit();
    }
}

// Regenerate token for GET requests
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
header("Location: {$base_path}index.php");
exit();