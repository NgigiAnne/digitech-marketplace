<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
require_once __DIR__ . '/../includes/database.php';

// Validate token
$token = $_POST["token"] ?? null;
if (!$token) {
    $_SESSION['reset_error'] = "Invalid token";
    header("Location: reset-password.php?token=" . urlencode($token));
    exit();
}

// Get database connection
$mysqli = getDB();
if (!($mysqli instanceof mysqli) || $mysqli->connect_error) {
    $_SESSION['reset_error'] = "Database connection error";
    header("Location: reset-password.php?token=" . urlencode($token));
    exit();
}

// Validate token
$token_hash = hash("sha256", $token);
$sql = "SELECT * FROM users WHERE reset_token_hash = ?";
$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    $_SESSION['reset_error'] = "Database error: " . $mysqli->error;
    header("Location: reset-password.php?token=" . urlencode($token));
    exit();
}

$stmt->bind_param("s", $token_hash);

if (!$stmt->execute()) {
    $_SESSION['reset_error'] = "Database error: " . $stmt->error;
    header("Location: reset-password.php?token=" . urlencode($token));
    exit();
}

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user === null) {
    $_SESSION['reset_error'] = "Invalid or expired reset token";
    header("Location: reset-password.php?token=" . urlencode($token));
    exit();
}

if (strtotime($user["reset_token_expires_at"]) <= time()) {
    $_SESSION['reset_error'] = "Reset token has expired";
    header("Location: reset-password.php?token=" . urlencode($token));
    exit();
}

// Validate password
$password = $_POST["password"] ?? '';
$password_confirmation = $_POST["password_confirmation"] ?? '';

if (strlen($password) < 8) {
    $_SESSION['reset_error'] = "Password must be at least 8 characters";
    header("Location: reset-password.php?token=" . urlencode($token));
    exit();
}

if (!preg_match("/[a-z]/i", $password)) {
    $_SESSION['reset_error'] = "Password must contain at least one letter";
    header("Location: reset-password.php?token=" . urlencode($token));
    exit();
}

if (!preg_match("/[0-9]/", $password)) {
    $_SESSION['reset_error'] = "Password must contain at least one number";
    header("Location: reset-password.php?token=" . urlencode($token));
    exit();
}

if ($password !== $password_confirmation) {
    $_SESSION['reset_error'] = "Passwords do not match";
    header("Location: reset-password.php?token=" . urlencode($token));
    exit();
}

// Hash the new password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Update user password and clear token - USE 'password' COLUMN HERE
$sql = "UPDATE users
        SET password = ?,  /* Changed from password_hash to password */
            reset_token_hash = NULL,
            reset_token_expires_at = NULL
        WHERE id = ?";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    $_SESSION['reset_error'] = "Database error: " . $mysqli->error;
    header("Location: reset-password.php?token=" . urlencode($token));
    exit();
}

// Bind parameters
$stmt->bind_param("si", $password_hash, $user["id"]);

if (!$stmt->execute()) {
    $_SESSION['reset_error'] = "Database error: " . $stmt->error;
    header("Location: reset-password.php?token=" . urlencode($token));
    exit();
}

// Check if update was successful
if ($stmt->affected_rows === 0) {
    $_SESSION['reset_error'] = "Password update failed. No rows affected.";
    header("Location: reset-password.php?token=" . urlencode($token));
    exit();
}

// Password reset successful
$_SESSION['login_success'] = "Password updated successfully. You can now login with your new password.";
header("Location: ../index.php");
exit();
?>