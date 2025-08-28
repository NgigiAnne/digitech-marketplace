<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
session_start();

// 1. Clear all session data
$_SESSION = [];

// 2. Destroy the session completely
session_destroy();

// 3. Delete the remember_me cookie if it exists
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/', '', true, true); // Secure flags
}

// 4. Redirect to login form
header("Location: index.php"); // Goes to digitech/index.php
exit();
?>