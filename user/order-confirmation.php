<?php
// order-confirmation.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Keep this for debugging, remove in production!

require_once(__DIR__ . '/../includes/auth.php');
require_once(__DIR__ . '/../includes/database.php');
require_once(__DIR__ . '/../includes/header.php'); // Assuming header.php handles conditional session_start()

if (!is_logged_in()) {
    // Redirect to login page if not logged in. Make sure this path is correct.
    header('Location: /Digitech/user/dashboard.php'); // Or your actual login page path
    exit();
}

// Get the order ID from the URL, defaulting to null if not set
$order_id = $_GET['id'] ?? null;

$conn = getDB();
$user_id = $_SESSION['user_id'];
$order = []; // Initialize order array
$order_items = []; // Initialize items array
$error_message = ''; // For user-friendly error messages

if (!$order_id) {
    $error_message = 'No order ID provided. Please navigate from your orders history or products page.';
} else {
    try {
        // Fetch order details including shipping address and phone number
        $stmt = $conn->prepare("SELECT o.order_id, o.total, o.status, o.created_at, o.payment_method, o.shipping_address, o.phone_number, u.name AS user_name, u.email AS user_email
                                 FROM orders o
                                 JOIN users u ON o.user_id = u.id
                                 WHERE o.order_id = ? AND o.user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error_message = 'Order not found or you do not have permission to view this order.';
            // Consider redirecting to 'my_orders.php' or 'dashboard.php' for a softer landing
        } else {
            $order = $result->fetch_assoc();

            // Fetch order items
            $stmt = $conn->prepare("SELECT oi.quantity, oi.price, p.name, p.image_url AS image
                                     FROM order_items oi
                                     LEFT JOIN products p ON oi.product_id = p.id
                                     WHERE oi.order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result_items = $stmt->get_result();
            $order_items = $result_items->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Exception $e) {
        $error_message = 'An error occurred while fetching order details. Please try again later.';
        error_log("Order confirmation error: " . $e->getMessage()); // Log detailed error
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation | DigiTech</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== Header ===== */
header {
  background-color: var(--white);
  padding: 1rem 2rem;
  box-shadow: var(--shadow-md);
  position: sticky;
  top: 0;
  z-index: 1000;
  transition: var(--transition);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

header.scrolled {
  padding: 0.75rem 2rem;
}

.logo {
  font-size: 1.8rem;
  font-weight: 800;
  color: var(--primary-color);
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.mobile-menu-toggle {
  display: none;
  background: none;
  border: none;
  font-size: 1.5rem;
  color: var(--dark-text);
  cursor: pointer;
}

/* Navigation */
.main-nav {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.main-nav a {
  color: var(--dark-text);
  font-weight: 600;
  position: relative;
  padding: 0.5rem 0;
}

.main-nav a:hover {
  color: var(--primary-color);
}

.main-nav a::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 0;
  height: 2px;
  background-color: var(--primary-color);
  transition: var(--transition);
}

.main-nav a:hover::after {
  width: 100%;
}

.cart-count {
  background-color: var(--primary-color);
  color: var(--white);
  border-radius: 50%;
  padding: 0.15rem 0.5rem;
  font-size: 0.75rem;
  margin-left: 0.25rem;
  font-weight: bold;
}

/* Search */
.search-container {
  position: relative;
  display: flex;
  align-items: center;
}

.search-bar {
  padding: 0.5rem 1rem;
  padding-right: 2.5rem;
  border-radius: 2rem;
  border: 1px solid var(--medium-gray);
  outline: none;
  transition: var(--transition);
  width: 200px;
}

.search-bar:focus {
  border-color: var(--primary-color);
  width: 250px;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.search-btn {
  position: absolute;
  right: 1rem;
  background: none;
  border: none;
  color: var(--dark-gray);
  cursor: pointer;
}
        /* Your CSS styles here (or link to an external stylesheet) */
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; color: #333; }
        .container { 
            max-width: 800px; margin: 30px auto; padding: 30px; 
            background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        .confirmation-message { text-align: center; margin-bottom: 30px; }
        .confirmation-message h1 { color: #28a745; font-size: 2.5rem; margin-bottom: 10px; }
        .confirmation-message .lead { color: #555; font-size: 1.1rem; }
        .order-details { border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px; text-align: left; }
        .order-details h3 { color: #007bff; margin-bottom: 15px; }
        .order-details p { margin-bottom: 8px; }
        .order-items { margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px; }
        .order-item { display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .order-item:last-child { border-bottom: none; }
        .order-item img { width: 80px; height: 80px; object-fit: cover; border-radius: 4px; margin-right: 15px; }
        .order-item h4 { margin: 0; font-size: 1.1rem; color: #333; }
        .order-item p { margin: 0; color: #777; font-size: 0.95rem; }
        .btn-primary { 
            display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; 
            text-decoration: none; border-radius: 5px; transition: background-color 0.3s ease;
        }
        .btn-primary:hover { background-color: #0056b3; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        /* ===== Footer ===== */
footer {
  background-color: #1e3a8a;
  color: var(--white);
  padding: 3rem 0 0;
}

.footer-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 2rem;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

.footer-section h4 {
  color: var(--white);
  margin-bottom: 1.5rem;
  font-size: 1.1rem;
}

.footer-section ul {
  list-style: none;
}

.footer-section li {
  margin-bottom: 0.75rem;
}

.footer-section a {
  color: rgba(255, 255, 255, 0.8);
  transition: var(--transition);
}

.footer-section a:hover {
  color: var(--white);
  text-decoration: underline;
}

.newsletter input {
  width: 100%;
  padding: 0.75rem;
  border: none;
  border-radius: var(--border-radius);
  margin-bottom: 1rem;
}

.newsletter button {
  width: 100%;
  padding: 0.75rem;
  background-color: var(--primary-color);
  color: var(--white);
  border: none;
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: var(--transition);
}

.newsletter button:hover {
  background-color: var(--primary-dark);
}

.social-links {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.social-links a {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background-color: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  transition: var(--transition);
}

.social-links a:hover {
  background-color: var(--primary-color);
  transform: translateY(-3px);
}

.footer-bottom {
  text-align: center;
  padding: 1.5rem;
  margin-top: 2rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.payment-methods {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-top: 1rem;
  font-size: 1.5rem;
}

    /* Base Styles */
    :root {
        --primary-color: #2563eb;
        --primary-dark: #1e40af;
        --secondary-color: #3b82f6;
        --success-color: #28a745;
        --danger-color: #dc3545;
        --light-gray: #f8f9fa;
        --medium-gray: #e9ecef;
        --dark-gray: #6c757d;
        --dark-text: #212529;
        --white: #ffffff;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.12);
        --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
        --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
        --border-radius: 8px;
        --transition: all 0.3s ease;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.6;
        background-color: #f9fafb;
        color: var(--dark-text);
    }

    /* Container */
    .container {
        max-width: 900px;
        margin: 40px auto;
        padding: 40px;
        background: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-md);
        position: relative;
        overflow: hidden;
    }

    .container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 8px;
        background: linear-gradient(90deg, var(--primary-color), var(--success-color));
    }

    /* Confirmation Message */
    .confirmation-message {
        text-align: center;
        margin-bottom: 40px;
        position: relative;
    }

    .confirmation-message h1 {
        color: var(--success-color);
        font-size: 2.5rem;
        margin-bottom: 15px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .confirmation-message .lead {
        color: var(--dark-gray);
        font-size: 1.2rem;
        max-width: 600px;
        margin: 0 auto 30px;
    }

    /* Order Details */
    .order-details {
        border-top: 1px solid var(--medium-gray);
        padding-top: 30px;
        margin-top: 30px;
    }

    .order-details h3 {
        color: var(--primary-color);
        margin-bottom: 20px;
        font-size: 1.5rem;
        position: relative;
        padding-bottom: 10px;
    }

    .order-details h3::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 60px;
        height: 3px;
        background: var(--primary-color);
    }

    .order-details p {
        margin-bottom: 12px;
        display: flex;
    }

    .order-details p strong {
        min-width: 150px;
        color: var(--dark-gray);
    }

    /* Order Items */
    .order-items {
        margin-top: 30px;
        border-top: 1px solid var(--medium-gray);
        padding-top: 20px;
    }

    .order-item {
        display: flex;
        align-items: center;
        padding: 20px;
        border-radius: var(--border-radius);
        background: var(--light-gray);
        margin-bottom: 15px;
        transition: var(--transition);
    }

    .order-item:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-sm);
    }

    .order-item img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: var(--border-radius);
        margin-right: 20px;
        border: 1px solid var(--medium-gray);
    }

    .order-item-info {
        flex: 1;
    }

    .order-item h4 {
        margin: 0 0 5px;
        font-size: 1.1rem;
        color: var(--dark-text);
    }

    .order-item p {
        margin: 0;
        color: var(--dark-gray);
        font-size: 0.95rem;
    }

    .order-item-price {
        font-weight: 600;
        color: var(--primary-color);
        font-size: 1.1rem;
    }

    /* Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: var(--border-radius);
        font-weight: 600;
        text-decoration: none;
        transition: var(--transition);
        border: none;
        cursor: pointer;
    }

    .btn-primary {
        background-color: var(--primary-color);
        color: var(--white);
    }

    .btn-primary:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: var(--shadow-sm);
    }

    .btn-outline {
        background: transparent;
        color: var(--primary-color);
        border: 2px solid var(--primary-color);
    }

    .btn-outline:hover {
        background: var(--primary-color);
        color: var(--white);
    }

    /* Alert Messages */
    .alert {
        padding: 20px;
        border-radius: var(--border-radius);
        margin-bottom: 30px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .alert i {
        font-size: 1.5rem;
        margin-bottom: 10px;
    }

    .text-success {
        color: var(--success-color);
    }

    .text-danger {
        color: var(--danger-color);
    }

    /* Status Badges */
    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .status-processing {
        background-color: #fff3cd;
        color: #856404;
    }

    .status-completed {
        background-color: #d4edda;
        color: #155724;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .container {
            padding: 30px 20px;
            margin: 20px;
        }

        .order-item {
            flex-direction: column;
            text-align: center;
        }

        .order-item img {
            margin-right: 0;
            margin-bottom: 15px;
        }

        .order-details p {
            flex-direction: column;
        }

        .order-details p strong {
            margin-bottom: 5px;
        }
    }

    /* Animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .confirmation-message, 
    .order-details, 
    .order-items {
        animation: fadeIn 0.6s ease-out forwards;
    }

    .order-items {
        animation-delay: 0.2s;
    }

    </style>
</head>
<body>

<div class="container">
    <?php if (!empty($error_message)): ?>
        <div class="alert text-danger" style="background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
            <p class="mt-2"><a href="/Digitech/index.php" class="btn btn-primary">Go to Home</a></p>
        </div>
    <?php elseif (empty($order)): ?>
        <div class="alert text-danger" style="background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;">
            <i class="fas fa-exclamation-triangle"></i> No order details available.
            <p class="mt-2"><a href="/Digitech/index.php" class="btn btn-primary">Go to Home</a></p>
        </div>
    <?php else: ?>
        <div class="confirmation-message">
            <h1><i class="fas fa-check-circle text-success"></i> Order Confirmed!</h1>
            <p class="lead">Thank you for your purchase. Your order has been received and is being processed.</p>
            
            <div class="order-details">
                <h3>Order Details</h3>
                <p><strong>Order Number:</strong> #<?= htmlspecialchars($order['order_id']) ?></p>
                <p><strong>Date:</strong> <?= htmlspecialchars(date('F j, Y', strtotime($order['created_at']))) ?></p>
                <p><strong>Total:</strong> KSh <?= htmlspecialchars(number_format($order['total'], 2)) ?></p>
                <p><strong>Payment Method:</strong> <?= htmlspecialchars(ucwords(str_replace('_', ' ', $order['payment_method'] ?? 'N/A'))) ?></p>
                <?php if (!empty($order['shipping_address'])): ?>
                    <p><strong>Shipping Address:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                <?php endif; ?>
                <?php if (!empty($order['phone_number'])): ?>
                    <p><strong>Phone Number:</strong> <?= htmlspecialchars($order['phone_number']) ?></p>
                <?php endif; ?>
                
                <h3 class="mt-4">Order Items</h3>
                <div class="order-items">
                    <?php if (!empty($order_items)): ?>
                        <?php foreach ($order_items as $item): ?>
                            <div class="order-item">
                      
                                <div>
                                  <h4><?= htmlspecialchars($item['name'] ?? '') ?></h4>



                                    <p>KSh <?= htmlspecialchars(number_format($item['price'], 2)) ?> × <?= htmlspecialchars($item['quantity']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No items found for this order.</p>
                    <?php endif; ?>
                </div>
            </div>
           

            <a href="dashboard.php" class="btn btn-primary mt-4">
                <i class="fas fa-arrow-left"></i> Continue Shopping
            </a>
        </div>
    <?php endif; ?>
</div>


</body>
</html>