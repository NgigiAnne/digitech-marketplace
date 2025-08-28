<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Redirect if not logged in
if (!is_logged_in()) {
    $_SESSION['redirect_to'] = '/user/checkout.php'; // Absolute path for redirect
    header('Location: ../user/dashboard.php'); // Adjust path as needed
    exit();
}

$errors = []; // Initialize error array
$cart = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // This branch handles both the initial 'cart_data' POST (from proceeding to checkout)
    // and the final form submission POST (via fetch API).

    if (isset($_POST['cart_data'])) {
        // This is the initial POST navigation from the client-side JavaScript,
        // typically when a user clicks "Proceed to Checkout" from their cart page.
        $cart_data_json = $_POST['cart_data'];
        $decoded_cart = json_decode($cart_data_json, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_cart)) {
            $cart = $decoded_cart;
            $_SESSION['cart'] = $cart; // Store cart in session for resilience (e.g., page refresh)
        } else {
            $errors[] = 'Invalid cart data received.';
            // Fallback to session cart if POSTed data is bad, or default to empty
            $cart = $_SESSION['cart'] ?? [];
        }
    } else {
        // This is a form submission POST, handled by the client-side fetch API.
        // The request body will be JSON.
        $posted_data = json_decode(file_get_contents('php://input'), true);

        // Ensure cart data is present in the POSTed data for order processing
        $form_submitted_cart = $posted_data['cart'] ?? [];

        if (empty($form_submitted_cart)) {
            $errors[] = 'Your cart is empty. Please add items before completing your order.';
        } else {
            // Validate form fields
            $required_fields = ['full_name', 'email', 'phone', 'address', 'payment_method'];
            foreach ($required_fields as $field) {
                if (empty($posted_data[$field])) {
                    // Collect more specific error messages for client-side display
                    if ($field === 'full_name') $errors[] = "Full Name is required.";
                    else if ($field === 'email') $errors[] = "Email Address is required.";
                    else if ($field === 'phone') $errors[] = "Phone Number is required.";
                    else if ($field === 'address') $errors[] = "Shipping Address is required.";
                    else if ($field === 'payment_method') $errors[] = "Payment Method is required.";
                }
            }

            // Basic email format validation
            if (!empty($posted_data['email']) && !filter_var($posted_data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Please enter a valid email address.";
            }

            if (empty($errors)) {
                try {
                    $conn = getDB(); // Get database connection from includes/database.php
                    $conn->begin_transaction(); // Start transaction for atomicity

                    // 1. Create the order record
                    // Calculate total from the cart data received in the POST request
                    $total = array_reduce($form_submitted_cart, fn($sum, $item) => $sum + (($item['price'] ?? 0) * ($item['quantity'] ?? 0)), 0);

                    // Updated INSERT statement to include new shipping and payment details
                    $stmt = $conn->prepare("INSERT INTO orders (user_id, total, status, shipping_address, phone_number, email, payment_method) VALUES (?, ?, 'pending', ?, ?, ?, ?)");
                    $stmt->bind_param(
                        "idssss",
                        $_SESSION['user_id'],
                        $total,
                        $posted_data['address'],
                        $posted_data['phone'],
                        $posted_data['email'],
                        $posted_data['payment_method']
                    );
                    $stmt->execute();
                    $order_id = $conn->insert_id;

                    // 2. Add order items
                    // Updated INSERT statement for order_items to include image_url
                    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, image_url) VALUES (?, ?, ?, ?, ?)");
                    foreach ($form_submitted_cart as $item) {
                        $stmt->bind_param("iiids", $order_id, $item['id'], $item['quantity'], $item['price'], $item['image']);
                        $stmt->execute();
                    }

                    $conn->commit(); // Commit the transaction if all operations succeed

                    // Clear the cart from the session after successful order
                    unset($_SESSION['cart']);

                    // Send success response and redirect URL to client-side JS
                    echo json_encode(['success' => true, 'redirect' => '/Digitech/user/order-confirmation.php?id=' . $order_id]);
                    exit(); // Terminate script after sending JSON response

                } catch (Exception $e) {
                    $conn->rollback(); // Rollback transaction on error
                    $errors[] = "Checkout failed. Please try again.";
                    error_log("Checkout Error: " . $e->getMessage()); // Log detailed error
                }
            }
        }
        // If there are errors during form submission, send them back in JSON format
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit(); // Terminate script after sending JSON response
    }
} else {
    // This is a GET request (e.g., direct URL access or page refresh).
    // Try to get the cart from the session as a fallback.
    $cart = $_SESSION['cart'] ?? [];

    // If cart is empty on a GET request, redirect the user back to the shop
    if (empty($cart)) {
        $_SESSION['error'] = 'Your cart is empty. Please add items before checking out.';
        header('Location: ../index.php'); // Adjust path as needed
        exit();
    }
}

// Include header (adjust path as needed)
//include(__DIR__ . '/../includes/header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | DigiTech</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== Base Styles & Variables ===== */
:root {
    --primary: #4361ee;
    --primary-light: #e0e7ff;
    --primary-dark: #3a0ca3;
    --secondary: #3f37c9;
    --success: #4cc9f0;
    --danger: #f72585;
    --warning: #f8961e;
    --info: #4895ef;
    --dark: #1b263b;
    --dark-gray: #6c757d;
    --medium-gray: #adb5bd;
    --light-gray: #e9ecef;
    --light: #f8f9fa;
    --white: #ffffff;
    --border-radius-sm: 4px;
    --border-radius: 8px;
    --border-radius-lg: 12px;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
    --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
    --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
    --transition: all 0.3s ease;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background-color: var(--light);
    color: var(--dark);
    line-height: 1.6;
    font-size: 16px;
}

/* ===== Layout & Containers ===== */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.checkout-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 2.5rem;
    margin: 2rem 0;
    align-items: flex-start;
}

@media (max-width: 768px) {
    .checkout-grid {
        grid-template-columns: 1fr;
    }
}

/* ===== Header Styles ===== */
header {
    background-color: var(--white);
    padding: 1rem 2rem;
    box-shadow: var(--shadow-md);
    position: sticky;
    top: 0;
    z-index: 1000;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
}

.main-nav {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.main-nav a {
    color: var(--dark);
    font-weight: 600;
    text-decoration: none;
    position: relative;
    padding: 0.5rem 0;
    transition: var(--transition);
}

.main-nav a:hover {
    color: var(--primary);
}

.main-nav a::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background-color: var(--primary);
    transition: var(--transition);
}

.main-nav a:hover::after {
    width: 100%;
}

.cart-count {
    background-color: var(--primary);
    color: var(--white);
    border-radius: 50%;
    padding: 0.15rem 0.5rem;
    font-size: 0.75rem;
    margin-left: 0.25rem;
    font-weight: bold;
}

/* ===== Form Styles ===== */
.checkout-form {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: 2.5rem;
    box-shadow: var(--shadow-md);
    transition: var(--transition);
}

.checkout-form:hover {
    box-shadow: var(--shadow-lg);
}

h2 {
    color: var(--dark);
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

h2 i {
    color: var(--primary);
}

.form-group {
    margin-bottom: 1.75rem;
    position: relative;
}

label {
    display: block;
    margin-bottom: 0.75rem;
    font-weight: 600;
    color: var(--dark);
}

.required-field::after {
    content: '*';
    color: var(--danger);
    margin-left: 0.25rem;
}

.form-control {
    width: 100%;
    padding: 1rem;
    border: 1px solid var(--light-gray);
    border-radius: var(--border-radius);
    font-size: 1rem;
    transition: var(--transition);
    background-color: var(--white);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-light);
}

textarea.form-control {
    min-height: 120px;
    resize: vertical;
}

/* ===== Payment Method Styles ===== */
.payment-methods-container {
    margin-top: 1.5rem;
}

.payment-method {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
    padding: 1.25rem;
    border: 1px solid var(--light-gray);
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: var(--transition);
    background-color: var(--white);
}

.payment-method:hover {
    border-color: var(--primary);
    background-color: var(--primary-light);
}

.payment-method.selected {
    border-color: var(--primary);
    background-color: var(--primary-light);
    box-shadow: 0 0 0 1px var(--primary);
}

.payment-method input[type="radio"] {
    opacity: 0;
    position: absolute;
}

.payment-method i {
    font-size: 1.75rem;
    color: var(--primary);
    width: 30px;
    text-align: center;
}

.payment-method span {
    flex-grow: 1;
    font-weight: 500;
}

/* ===== Button Styles ===== */
.btn {
    display: inline-block;
    padding: 1.125rem 1.5rem;
    background-color: var(--primary);
    color: var(--white);
    border: none;
    border-radius: var(--border-radius);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    text-align: center;
    width: 100%;
    box-shadow: var(--shadow-sm);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 1rem;
}

.btn:hover:not(:disabled) {
    background-color: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn:active:not(:disabled) {
    transform: translateY(0);
}

.btn i {
    margin-right: 0.75rem;
}

.btn:disabled {
    background-color: var(--medium-gray);
    cursor: not-allowed;
    opacity: 0.7;
}

/* ===== Order Summary Styles ===== */
.order-summary {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-md);
    position: sticky;
    top: 1rem;
}

.order-items {
    max-height: 400px;
    overflow-y: auto;
    padding-right: 0.5rem;
}

.order-item {
    display: flex;
    gap: 1.25rem;
    padding: 1.25rem 0;
    border-bottom: 1px solid var(--light-gray);
    align-items: center;
}

.order-item:last-child {
    border-bottom: none;
}

.order-item img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
}

.order-item-details {
    flex-grow: 1;
}

.order-item h4 {
    font-size: 1rem;
    margin-bottom: 0.5rem;
    color: var(--dark);
    font-weight: 600;
}

.order-item p {
    color: var(--dark-gray);
    font-size: 0.9rem;
}

.order-total {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 2px solid var(--light-gray);
    display: flex;
    justify-content: space-between;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--dark);
}

/* ===== Alert & Error Styles ===== */
.alert {
    padding: 1.25rem;
    margin-bottom: 2rem;
    border-radius: var(--border-radius);
    display: block;
}

.alert-danger {
    background-color: #fee2e2;
    border-left: 4px solid var(--danger);
    color: #b91c1c;
}

.error-message {
    color: var(--danger);
    font-size: 0.875rem;
    margin-top: 0.5rem;
    display: none;
}

.form-group.has-error .form-control,
.form-group.has-error .payment-method {
    border-color: var(--danger);
}

.form-group.has-error .error-message {
    display: block;
}

/* ===== Footer Styles ===== */
footer {
    background-color: var(--dark);
    color: var(--white);
    padding: 3rem 0 0;
    margin-top: 3rem;
}

.footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
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
    text-decoration: none;
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
    background-color: var(--primary);
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
    color: var(--white);
    text-decoration: none;
}

.social-links a:hover {
    background-color: var(--primary);
    transform: translateY(-3px);
}

.footer-bottom {
    text-align: center;
    padding: 1.5rem;
    margin-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.7);
}

.payment-methods {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 1rem;
    font-size: 1.5rem;
    color: rgba(255, 255, 255, 0.7);
}

/* ===== Animations ===== */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.fa-spinner {
    animation: spin 1s linear infinite;
}

/* ===== Responsive Adjustments ===== */
@media (max-width: 992px) {
    .container {
        padding: 0 1.25rem;
    }
    
    .checkout-form, .order-summary {
        padding: 2rem;
    }
}

@media (max-width: 768px) {
    header {
        padding: 1rem;
    }
    
    .logo {
        font-size: 1.5rem;
    }
    
    .checkout-grid {
        gap: 1.5rem;
    }
    
    .order-summary {
        position: static;
    }
}

@media (max-width: 576px) {
    .checkout-form, .order-summary {
        padding: 1.5rem;
    }
    
    .order-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .order-item img {
        width: 100%;
        height: auto;
        max-height: 120px;
    }
    
    .btn {
        padding: 1rem;
    }
}
    </style>
</head>
<body>
    <header>
    <a href="/dashboard.php" class="logo">DigiTech</a>
    <nav class="main-nav">
       <a href="dashboard.php" class="btn-back">
    <i class="fas fa-arrow-left"></i> Back to Home
</a>
    </nav>
</header>
    <div class="container">
        <?php
        // Display initial PHP errors (e.g., invalid cart data on first POST)
        if (!empty($errors)): ?>
            <div class="alert alert-danger" id="php-errors-display">
                <h3><i class="fas fa-exclamation-triangle"></i> Please fix these issues:</h3>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="checkout-grid">
            <form id="checkoutForm" method="POST">
                <h2><i class="fas fa-shipping-fast"></i> Shipping Information</h2>
                <div class="form-group">
                    <label for="full_name">Full Name <span style="color:var(--danger);">*</span></label>
                    <input type="text" id="full_name" name="full_name" class="form-control" required value="<?= htmlspecialchars($_SESSION['user']['full_name'] ?? '') ?>">
                    <small class="error-message" data-field="full_name"></small>
                </div>
                <div class="form-group">
                    <label for="email">Email Address <span style="color:var(--danger);">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?>">
                    <small class="error-message" data-field="email"></small>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number <span style="color:var(--danger);">*</span></label>
                    <input type="tel" id="phone" name="phone" class="form-control" required>
                    <small class="error-message" data-field="phone"></small>
                </div>
                <div class="form-group">
                    <label for="address">Shipping Address <span style="color:var(--danger);">*</span></label>
                    <textarea id="address" name="address" class="form-control" rows="3" required></textarea>
                    <small class="error-message" data-field="address"></small>
                </div>

                <h2><i class="fas fa-credit-card"></i> Payment Method</h2>
                <div class="form-group">
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="mpesa" required>
                        <i class="fas fa-mobile-alt"></i> <span>M-Pesa</span>
                    </label>
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="card">
                        <i class="fas fa-credit-card"></i> <span>Credit/Debit Card</span>
                    </label>
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="paypal">
                        <i class="fab fa-paypal"></i> <span>PayPal</span>
                    </label>
                    <small class="error-message" data-field="payment_method"></small>
                </div>
                <button type="submit" class="btn" id="completeOrderBtn"><i class="fas fa-money-check-alt"></i> Complete Order</button>
            </form>

            <div class="order-summary">
                <h2><i class="fas fa-shopping-cart"></i> Order Summary</h2>
                <?php if (!empty($cart)): ?>
                    <?php foreach ($cart as $item): ?>
                        <div class="order-item">
                            <img src="<?= htmlspecialchars($item['image'] ?? '/path/to/default-placeholder.jpg') ?>" alt="<?= htmlspecialchars($item['name'] ?? 'Product') ?>">
                            <div>
                                <h4><?= htmlspecialchars($item['name'] ?? 'Unknown Product') ?></h4>
                                <p>KSh <?= htmlspecialchars(number_format($item['price'] ?? 0, 2)) ?> &times; <?= htmlspecialchars($item['quantity'] ?? '1') ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php
                    $total = array_reduce($cart, fn($sum, $item) => $sum + (($item['price'] ?? 0) * ($item['quantity'] ?? 0)), 0);
                    ?>
                    <div class="order-total">Total: KSh <?= htmlspecialchars(number_format($total, 2)) ?></div>
                <?php else: ?>
                    <p>Your cart is empty. Please go back to the <a href="/Digitech/index.php">shop</a> to add items.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const checkoutForm = document.getElementById('checkoutForm');
    const completeOrderBtn = document.getElementById('completeOrderBtn');
    const phpErrorsDiv = document.getElementById('php-errors-display');

    // Function to clear all previous error messages and styling
    function clearErrors() {
        document.querySelectorAll('.form-group.has-error').forEach(group => {
            group.classList.remove('has-error');
        });
        document.querySelectorAll('.error-message').forEach(msg => {
            msg.textContent = '';
        });
        if (phpErrorsDiv) {
            phpErrorsDiv.style.display = 'none'; // Hide the initial PHP error div
            phpErrorsDiv.innerHTML = ''; // Clear its content
        }
    }

    // Function to display errors received from the server
    function displayErrors(errors) {
        clearErrors(); // Clear existing errors first

        const genericErrors = []; // To store errors not tied to a specific form field

        errors.forEach(errorText => {
            let handled = false;
            // Map error messages from PHP to specific form fields for display
            if (errorText.includes("Full Name is required")) {
                setErrorForField('full_name', 'Full Name is required.');
                handled = true;
            } else if (errorText.includes("Email Address is required") || errorText.includes("valid email address")) {
                setErrorForField('email', errorText.includes("valid email") ? 'Please enter a valid email address.' : 'Email Address is required.');
                handled = true;
            } else if (errorText.includes("Phone Number is required")) {
                setErrorForField('phone', 'Phone Number is required.');
                handled = true;
            } else if (errorText.includes("Shipping Address is required")) {
                setErrorForField('address', 'Shipping Address is required.');
                handled = true;
            } else if (errorText.includes("Payment Method is required")) {
                setErrorForField('payment_method', 'Please select a payment method.');
                handled = true;
            } else {
                // If error doesn't match a specific field, add to generic errors
                genericErrors.push(errorText);
            }
        });

        // Display any remaining generic errors in the general alert area
        if (genericErrors.length > 0) {
            if (phpErrorsDiv) {
                phpErrorsDiv.innerHTML = `<h3><i class="fas fa-exclamation-triangle"></i> Please fix these issues:</h3><ul>${genericErrors.map(e => `<li>${htmlspecialchars(e)}</li>`).join('')}</ul>`;
                phpErrorsDiv.style.display = 'block';
            } else {
                // Fallback alert if for some reason the PHP error div is missing
                alert('Checkout failed: \n' + genericErrors.join('\n'));
            }
        }
    }

    // Helper function to apply error styling and message to a specific form field
    function setErrorForField(fieldName, message) {
        const inputElement = document.getElementById(fieldName) || document.querySelector(`[name="${fieldName}"]`);
        if (inputElement) {
            const formGroup = inputElement.closest('.form-group');
            if (formGroup) {
                formGroup.classList.add('has-error');
                const errorMessageElement = formGroup.querySelector('.error-message');
                if (errorMessageElement) {
                    errorMessageElement.textContent = message;
                }
            }
        } else if (fieldName === 'payment_method') {
            // Special handling for the payment method radio button group
            const paymentMethodErrorDiv = document.querySelector('.form-group .error-message[data-field="payment_method"]');
            if (paymentMethodErrorDiv) {
                paymentMethodErrorDiv.textContent = message;
                paymentMethodErrorDiv.closest('.form-group').classList.add('has-error');
            }
        }
    }

    // Main form submission handler
    checkoutForm.addEventListener('submit', async function(e) {
        e.preventDefault(); // Prevent default form submission

        clearErrors(); // Clear any existing error messages from previous attempts

        completeOrderBtn.disabled = true; // Disable the button to prevent double clicks
        completeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...'; // Show loading spinner feedback

        const formData = new FormData(this); // Get form data
        const data = Object.fromEntries(formData.entries()); // Convert to plain object

        // Retrieve the cart from localStorage for the final order submission.
        // This ensures the most up-to-date cart is used, consistent with client-side.
        const cart = JSON.parse(localStorage.getItem('cart')) || [];

        if (cart.length === 0) {
            alert('Your cart is empty. Please add items before completing your order.');
            completeOrderBtn.disabled = false;
            completeOrderBtn.innerHTML = '<i class="fas fa-money-check-alt"></i> Complete Order';
            return; // Stop execution if cart is empty
        }

        try {
            // Send the form data and cart data as JSON to the server
            const response = await fetch('/Digitech/user/checkout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ...data, // Spread form data
                    cart: cart // Add cart data
                })
            });

            const result = await response.json(); // Parse JSON response from server

            if (result.success) {
                localStorage.removeItem('cart'); // Clear client-side cart after successful order
                window.location.href = result.redirect; // Redirect to order confirmation page
            } else {
                // If server returns errors, display them to the user
                if (result.errors && result.errors.length > 0) {
                    displayErrors(result.errors); // Use the dedicated function for error display
                } else {
                    alert('Checkout failed. Please try again.'); // Generic error if no specific messages
                }
            }
        } catch (error) {
            console.error('Error during checkout submission:', error);
            alert('A network error occurred or the server did not respond. Please try again.');
        } finally {
            completeOrderBtn.disabled = false; // Re-enable the button regardless of success/failure
            completeOrderBtn.innerHTML = '<i class="fas fa-money-check-alt"></i> Complete Order'; // Restore button text
        }
    });

    // Initial check: Disable submit button if the PHP-rendered cart is empty
    const orderSummaryContent = document.querySelector('.order-summary').innerText;
    if (orderSummaryContent.includes('Your cart is empty')) {
        completeOrderBtn.disabled = true;
    }
    
    // Add event listeners to payment method radio buttons for visual feedback
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', () => {
            // Remove 'selected' class from all payment method labels
            document.querySelectorAll('.payment-method').forEach(label => {
                label.classList.remove('selected');
            });
            // Add 'selected' class to the parent label of the checked radio button
            if (radio.checked) {
                radio.closest('.payment-method').classList.add('selected');
            }
        });
    });

    // Simple HTML escaping function for security (client-side)
    function htmlspecialchars(str) {
        if (typeof str !== 'string') {
            str = String(str);
        }
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
</script>

<?php
// Include footer (adjust path as needed)
include(__DIR__ . '/../includes/footer.php');
?>