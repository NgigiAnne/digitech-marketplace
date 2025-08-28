<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start(); // Ensure session is started
require_once(__DIR__ . '/../includes/auth.php');
require_once(__DIR__ . '/../includes/database.php');

if (!is_logged_in()) {
    header('Location: /Digitech/user/dashboard.php'); // Or your login page
    exit();
}

$order_id = $_GET['order_id'] ?? null;
$user_id = $_SESSION['user_id'];
$conn = getDB();

if (!$order_id) {
    $_SESSION['error_message'] = "No order ID provided for payment processing.";
    header("Location: /Digitech/user/my_orders.php"); // Or dashboard
    exit();
}

// 1. Verify the order belongs to the user and is in 'pending_payment' status
$stmt = $conn->prepare("SELECT status FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order || $order['status'] !== 'pending_payment') {
    $_SESSION['error_message'] = "Order not found, not pending payment, or you don't have permission.";
    header("Location: /Digitech/user/my_orders.php"); // Or dashboard
    exit();
}

// --- Simulate Payment Gateway Interaction ---
// In a real application, you'd make an API call to a payment gateway here.
// The result of that API call would determine success or failure.

$payment_successful = (rand(0, 1) == 1); // Simulate 50% success rate

if ($payment_successful) {
    // 2. Update order status to 'processing' or 'completed'
    $stmt = $conn->prepare("UPDATE orders SET status = 'processing' WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->close();

    // Redirect to confirmation page with success flag
    header("Location: order-confirmation.php?id=" . $order_id . "&payment_success=true");
    exit();
} else {
    // 3. Update order status to 'payment_failed'
    $stmt = $conn->prepare("UPDATE orders SET status = 'payment_failed' WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->close();

    // Redirect to a payment failed page or display message
    $_SESSION['error_message'] = "Payment failed for Order #" . $order_id . ". Please try again or choose another method.";
    header("Location: /Digitech/user/payment_failed.php?order_id=" . $order_id); // Create this page
    exit();
}

$conn->close();
?>