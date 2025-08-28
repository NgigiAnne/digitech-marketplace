<?php
// send-password-reset.php
session_start();

// Include the database configuration file
require_once __DIR__ . '/../includes/database.php';

// Validate email input
if (empty($_POST["email"])) {
    $_SESSION['reset_error'] = "Email is required";
    header("Location: forgot-password.php");
    exit();
}

$email = $_POST["email"];

// Generate secure token
$token = bin2hex(random_bytes(16));
$token_hash = hash("sha256", $token);
$expiry = date("Y-m-d H:i:s", time() + 60 * 300); // 30 minutes expiration

// Get database connection
$mysqli = getDB();

// Verify we have a valid connection object
if (!($mysqli instanceof mysqli)) {
    $_SESSION['reset_error'] = "Database connection error";
    header("Location: forgot-password.php");
    exit();
}

// Prepare and execute SQL - using 'users' table instead of 'user'
$sql = "UPDATE users 
        SET reset_token_hash = ?, 
            reset_token_expires_at = ? 
        WHERE email = ?";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    $_SESSION['reset_error'] = "Database error: " . $mysqli->error;
    header("Location: forgot-password.php");
    exit();
}

$stmt->bind_param("sss", $token_hash, $expiry, $email);

if (!$stmt->execute()) {
    $_SESSION['reset_error'] = "Database error: " . $stmt->error;
    header("Location: forgot-password.php");
    exit();
}

// Only send email if user exists
if ($stmt->affected_rows) {
    // For demonstration, we'll simulate email sending
    // In a real application, you would integrate with PHPMailer or similar
    

    $mail = require __DIR__ . "/mailer.php";

    $mail->setFrom("muiruricharles666@gmail.com");
    $mail->addAddress($email);
    $mail->Subject = "Password Reset";
    $mail->Body = <<<END

    Click <a href="localhost/Digitech/user/reset-password.php?token=$token">here</a> 
    to reset your password.

    END;

    try {

        $mail->send();

    } catch (Exception $e) {

        echo "Message could not be sent. Mailer error: {$mail->ErrorInfo}";

    }

} else {
    $_SESSION['reset_error'] = "No account found with that email address";
}
echo "Message sent, please check your inbox.";
    $_SESSION['reset_status'] = "sent";
    $_SESSION['reset_email'] = $email;
    $_SESSION['reset_token'] = $token; // For demonstration only
// Redirect back to the form page
header("Location: forgot-password-visual.php");
exit();
?>