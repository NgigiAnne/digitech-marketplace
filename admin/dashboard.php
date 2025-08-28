<?php
// Enable detailed error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Check if user is logged in and is admin
if (!is_logged_in()) {
    header('Location: ../index.php');
    exit();
}

$conn = getDB();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../index.php');
    exit();
}

// Define allowed pages and set current page
$allowed_pages = ['dashboard', 'users', 'products', 'orders', 'reports'];
$current_page = isset($_GET['page']) && in_array($_GET['page'], $allowed_pages) ? $_GET['page'] : 'dashboard';

// Database functions with error handling
function getCount($conn, $query) {
    $result = $conn->query($query);
    if (!$result) {
        die("Query failed: " . $conn->error);
    }
    $row = $result->fetch_row();
    return $row[0];
}

function getData($conn, $query) {
    $data = [];
    $result = $conn->query($query);
    if (!$result) {
        die("Query failed: " . $conn->error);
    }
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

// Get statistics and data for the dashboard
$user_count = getCount($conn, "SELECT COUNT(*) FROM users");
$active_users = getCount($conn, "SELECT COUNT(*) FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 30 DAY)");
$new_users = getCount($conn, "SELECT COUNT(*) FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
$products = getData($conn, "SELECT * FROM products");
$users = ($current_page === 'users') ? getData($conn, "SELECT * FROM users") : [];
$orders_data = ($current_page === 'orders') ? getData($conn, "SELECT orders.*, users.name FROM orders JOIN users ON orders.user_id = users.id ORDER BY orders.created_at DESC") : []; // Renamed to orders_data to avoid conflict with $order variable in loops

// Initialize filter variables for orders
$order_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$order_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$order_status = isset($_GET['status']) ? $_GET['status'] : null;

// Build the orders query with filters
$orders_query = "SELECT orders.*, users.name FROM orders JOIN users ON orders.user_id = users.id WHERE 1=1";

if ($order_start_date) {
    $orders_query .= " AND orders.created_at >= '$order_start_date'";
}
if ($order_end_date) {
    $orders_query .= " AND orders.created_at <= '$order_end_date 23:59:59'";
}
if ($order_status) {
    $orders_query .= " AND orders.status = '$order_status'";
}

$orders_query .= " ORDER BY orders.created_at DESC";
$orders_data = ($current_page === 'orders') ? getData($conn, $orders_query) : [];
// Calculate total revenue, inventory value, and low stock items for reports page
$total_revenue = 0;
$recent_completed_orders_report = [];
$total_inventory_value = 0;
$low_stock_products = [];

// Initialize filter dates
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

if ($current_page === 'reports') {
    // Build date filter clause
    $date_filter_clause = '';
    $params = [];
    $param_types = '';

    if ($start_date) {
        $date_filter_clause .= " AND completed_at >= ?";
        $params[] = $start_date;
        $param_types .= 's';
    }
    if ($end_date) {
        $date_filter_clause .= " AND completed_at <= ?";
        $params[] = $end_date . ' 23:59:59'; // Include the whole end day
        $param_types .= 's';
    }

    $total_revenue_query = "SELECT SUM(total) FROM orders WHERE status = 'completed'" . $date_filter_clause;
    
    // Prepare statement for total revenue
    $stmt_revenue = $conn->prepare($total_revenue_query);
    if (!$stmt_revenue) {
        die("Prepare failed: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt_revenue->bind_param($param_types, ...$params);
    }

    $stmt_revenue->execute();
    $result_revenue = $stmt_revenue->get_result();
    $row_revenue = $result_revenue->fetch_row();
    $total_revenue = $row_revenue[0];
    $stmt_revenue->close();

    // Fetch recent completed orders for the report
    $recent_orders_query = "SELECT orders.*, users.name FROM orders JOIN users ON orders.user_id = users.id WHERE status = 'completed'" . $date_filter_clause . " ORDER BY completed_at DESC";
    
    // Prepare statement for recent completed orders
    $stmt_recent_orders = $conn->prepare($recent_orders_query);
    if (!$stmt_recent_orders) {
        die("Prepare failed: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt_recent_orders->bind_param($param_types, ...$params);
    }

    $stmt_recent_orders->execute();
    $result_recent_orders = $stmt_recent_orders->get_result();
    while($row = $result_recent_orders->fetch_assoc()) {
        $recent_completed_orders_report[] = $row;
    }
    $stmt_recent_orders->close();

    // Calculate total inventory value (this remains unchanged as it's not date-filtered)
    $inventory_products = getData($conn, "SELECT price, stock FROM products");
    foreach ($inventory_products as $product) {
        $total_inventory_value += ($product['price'] * $product['stock']);
    }

    // Fetch low stock products (this remains unchanged as it's not date-filtered)
    $low_stock_query = "SELECT id, name, stock, price FROM products WHERE stock < 10 ORDER BY stock ASC"; // Stock threshold set to 10
    $low_stock_products = getData($conn, $low_stock_query);
}

// Handle form submissions
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Add new product
        if (isset($_POST['add_product'])) {
            // Validate required fields
            $required = ['name', 'price', 'category', 'stock']; // Removed description from required if it's optional
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required.");
                }
            }

            // Prepare statement with correct columns (7 values)
            $stmt = $conn->prepare("INSERT INTO products (name, price, original_price, category, stock, description, image_url) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            // Set defaults for optional fields
            $original_price = $_POST['original_price'] ?? $_POST['price'];
            $description = $_POST['description'] ?? '';
            $image_url = $_POST['image_url'] ?? '';

            // Bind parameters with correct types: 
            // name (s), price (d), original_price (d), category (s), stock (i), description (s), image_url (s)
            $stmt->bind_param("sddisss",
                $_POST['name'],
                $_POST['price'], 
                $original_price,
                $_POST['category'],
                $_POST['stock'],
                $description,
                $image_url
            );
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            header("Location: dashboard.php?page=products&success=product_added");
            exit();
        }
        // Update product
        elseif (isset($_POST['update_product'])) {
            $required = ['name', 'price', 'category', 'stock', 'product_id'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required.");
                }
            }

            $stmt = $conn->prepare("UPDATE products SET 
                                     name = ?, 
                                     price = ?, 
                                     original_price = ?, 
                                     description = ?, 
                                     category = ?, 
                                     stock = ?,
                                     image_url = ? 
                                     WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            // Set defaults for optional fields
            $original_price = $_POST['original_price'] ?? $_POST['price'];
            $description = $_POST['description'] ?? '';
            $image_url = $_POST['image'] ?? ''; // Corrected from $_POST['image_url']
            
            // Corrected bind_param string to match 8 parameters for UPDATE
            $stmt->bind_param("sddssisi", // name, price, original_price, description, category, stock, image_url, id
                $_POST['name'],
                $_POST['price'], 
                $original_price,
                $description,
                $_POST['category'],
                $_POST['stock'],
                $image_url,
                $_POST['product_id'] // This was missing in the original bind_param
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            header("Location: dashboard.php?page=products&success=product_updated");
            exit();
        }
        // Delete product
        elseif (isset($_POST['delete_product'])) {
            if (empty($_POST['product_id'])) {
                throw new Exception("Product ID is required");
            }

            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("i", $_POST['product_id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            header("Location: dashboard.php?page=products&success=product_deleted");
            exit();
        }
        // Update user
        elseif (isset($_POST['update_user'])) {
            $required = ['name', 'email', 'role', 'user_id']; // Changed 'username' to 'name' based on common user table
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required.");
                }
            }

            $stmt = $conn->prepare("UPDATE users SET 
                                     name = ?, 
                                     email = ?, 
                                     role = ?
                                     WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("sssi",
                $_POST['name'], // Changed from username
                $_POST['email'],
                $_POST['role'],
                $_POST['user_id']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            header("Location: dashboard.php?page=users&success=user_updated");
            exit();
        }
        // Delete user
        elseif (isset($_POST['delete_user'])) {
            if (empty($_POST['user_id'])) {
                throw new Exception("User ID is required");
            }

            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("i", $_POST['user_id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            header("Location: dashboard.php?page=users&success=user_deleted");
            exit();
        }
        // Update order status
        elseif (isset($_POST['update_order_status'])) {
            $required = ['status', 'order_id'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required.");
                }
            }

            $sql = "UPDATE orders SET status = ?";
            if ($_POST['status'] === 'completed') {
                $sql .= ", completed_at = NOW()"; // Add completed_at update
            }
            $sql .= " WHERE order_id = ?";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("si", $_POST['status'], $_POST['order_id']);

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            header("Location: dashboard.php?page=orders&success=order_updated");
            exit();
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | DigiTech</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #2563eb;
      --primary-dark: #1e40af;
      --light-gray: #f9fafb;
      --medium-gray: #e5e7eb;
      --dark-gray: #6b7280;
      --white: #ffffff;
      --success-color: #10b981;
      --danger-color: #ef4444;
      --warning-color: #f59e0b;
      --card-shadow: 0 4px 6px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.1);
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--light-gray);
      color: #333;
      line-height: 1.6;
      display: flex;
      min-height: 100vh;
      overflow-x: hidden;
    }
    
    .admin-container {
      display: flex;
      flex: 1;
      min-height: 100%;
    }
    
    .sidebar {
      background: linear-gradient(180deg, #1e3a8a 0%, #0f2a61 100%);
      color: var(--white);
      padding: 1.5rem;
      box-shadow: 2px 0 10px rgba(0,0,0,0.1);
      width: 250px;
      min-height: 100vh;
      position: sticky;
      top: 0;
      flex-shrink: 0;
      z-index: 100;
    }
    
    .sidebar-header {
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .sidebar-header h2 {
      font-size: 1.5rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .sidebar-header i {
      color: #4ade80;
    }
    
    .sidebar-nav ul {
      list-style: none;
    }
    
    .sidebar-nav li {
      margin-bottom: 0.75rem;
    }
    
    .sidebar-nav a {
      color: rgba(255,255,255,0.8);
      text-decoration: none;
      display: flex;
      align-items: center;
      padding: 0.8rem 1rem;
      border-radius: 8px;
      transition: all 0.3s ease;
      font-size: 1.05rem;
    }
    
    .sidebar-nav a:hover, 
    .sidebar-nav a.active {
      color: var(--white);
      background-color: rgba(255,255,255,0.1);
      transform: translateX(5px);
    }
    
    .sidebar-nav i {
      width: 24px;
      text-align: center;
      margin-right: 0.8rem;
      font-size: 1.1rem;
    }
    
    .main-content {
      padding: 2rem;
      flex: 1;
      min-width: 0;
      transition: all 0.3s ease;
    }
    
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid var(--medium-gray);
      flex-wrap: wrap;
      gap: 1rem;
    }
    
    .header h1 {
      font-size: 2rem;
      font-weight: 700;
      color: #1e293b;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .user-info {
      display: flex;
      align-items: center;
      gap: 15px;
      flex-shrink: 0;
    }
    
    .user-info .avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: var(--primary-color);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Adjusted min-width for better fit */
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    
    .stat-card {
      background-color: var(--white);
      border-radius: 12px;
      padding: 1.8rem;
      box-shadow: var(--card-shadow);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      position: relative;
      overflow: hidden;
      display: flex; /* Added flex to align content within card */
      flex-direction: column; /* Stack content vertically */
      justify-content: space-between; /* Push icon to bottom or top */
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 15px rgba(0,0,0,0.1);
    }
    
    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 5px;
      height: 100%;
    }
    
    .stat-card.users::before {
      background-color: var(--primary-color);
    }
    
    .stat-card.active-users::before {
      background-color: var(--success-color);
    }
    
    .stat-card.new-users::before {
      background-color: var(--warning-color);
    }
    
    .stat-card.revenue::before { /* New class for revenue cards */
      background-color: #0d9488; /* Teal color for revenue */
    }

    .stat-card h3 {
      font-size: 1rem;
      color: var(--dark-gray);
      margin-bottom: 0.5rem;
      font-weight: 600;
    }
    
    .stat-card p {
      font-size: 2rem;
      font-weight: 700;
      color: #1e293b; /* Default text color for value */
    }
    
    .stat-card.users p {
      color: var(--primary-color);
    }
    
    .stat-card.active-users p {
      color: var(--success-color);
    }
    
    .stat-card.new-users p {
      color: var(--warning-color);
    }

    .stat-card.revenue p { /* Specific color for revenue values */
      color: #0d9488; /* Teal color */
    }
    
    .card {
      background-color: var(--white);
      border-radius: 12px;
      padding: 1.8rem;
      box-shadow: var(--card-shadow);
      margin-bottom: 2rem;
      transition: all 0.3s ease;
      overflow: hidden;
    }
    
    .card:hover {
      box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    }
    
    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      padding-bottom: 1.2rem;
      border-bottom: 1px solid var(--medium-gray);
      flex-wrap: wrap;
      gap: 1rem;
    }
    
    .card-header h2 {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1e293b;
    }
    
    .table-container {
      overflow-x: auto;
      margin-top: 1rem;
      border-radius: 8px;
      border: 1px solid var(--medium-gray);
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 800px;
    }
    
    th, td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid var(--medium-gray);
    }
    
    th {
      font-weight: 600;
      color: var(--dark-gray);
      background-color: #f8fafc;
    }
    
    tbody tr {
      transition: background-color 0.2s;
    }
    
    tbody tr:hover {
      background-color: #f8fafc;
    }
    
    .product-image {
      width: 50px;
      height: 50px;
      border-radius: 8px;
      object-fit: cover;
      background-color: #f1f5f9;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #94a3b8;
      font-size: 0.8rem;
      text-align: center;
    }
    
    .btn {
      padding: 0.6rem 1.2rem;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      white-space: nowrap;
    }
    
    .btn i {
      font-size: 0.9rem;
    }
    
    .btn-primary {
      background-color: var(--primary-color);
      color: var(--white);
    }
    
    .btn-primary:hover {
      background-color: var(--primary-dark);
      transform: translateY(-2px);
    }
    
    .btn-danger {
      background-color: var(--danger-color);
      color: var(--white);
    }
    
    .btn-danger:hover {
      background-color: #dc2626;
      transform: translateY(-2px);
    }
    
    .btn-sm {
      padding: 0.4rem 0.8rem;
      font-size: 0.85rem;
    }
    
    .form-group {
      margin-bottom: 1.2rem;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 0.6rem;
      font-weight: 600;
      color: #334155;
      font-size: 0.95rem;
    }
    
    .form-control {
      width: 100%;
      padding: 0.85rem;
      border: 1px solid var(--medium-gray);
      border-radius: 8px;
      font-size: 1rem;
      transition: border-color 0.3s;
    }
    
    .form-control:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    
    /* Modal Styles */
    .modal {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0,0,0,0.5);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 1000;
      backdrop-filter: blur(2px);
    }
    
    .modal.active {
      display: flex;
    }
    
    .modal-content {
      background-color: var(--white);
      border-radius: 12px;
      width: 90%;
      max-width: 500px;
      padding: 1.8rem;
      box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.2);
      max-height: 90vh;
      overflow-y: auto;
    }
    
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid #e2e8f0;
    }
    
    .modal-header h3 {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1e293b;
    }
    
    .close-btn {
      background: none;
      border: none;
      font-size: 1.8rem;
      cursor: pointer;
      color: #64748b;
      transition: color 0.2s;
    }
    
    .close-btn:hover {
      color: #475569;
    }
    
    .form-row {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }
    
    .form-row .form-group {
      flex: 1;
      min-width: 200px;
    }
    
    .alert {
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 500;
    }
    
    .alert i {
      font-size: 1.2rem;
    }
    
    .alert-success {
      background-color: #dcfce7;
      color: #166534;
      border-left: 4px solid #22c55e;
    }
    
    .alert-danger {
      background-color: #fee2e2;
      color: #991b1b;
      border-left: 4px solid #ef4444;
    }
    
    .preview-image {
      max-width: 100%;
      height: 150px;
      border-radius: 8px;
      object-fit: cover;
      margin-top: 0.5rem;
      display: none;
      background-color: #f1f5f9;
      border: 1px dashed #cbd5e1;
    }
    
    .preview-container {
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    
    /* Mobile Menu Toggle */
    .menu-toggle {
      display: none;
      background: none;
      border: none;
      color: var(--white);
      font-size: 1.5rem;
      position: absolute;
      top: 1.5rem;
      right: 1rem;
      z-index: 101;
      cursor: pointer;
    }
    
    /* Status Badges */
    .status-badge {
        display: inline-block;
        padding: 0.3em 0.8em;
        border-radius: 9999px; /* Fully rounded */
        font-size: 0.75em;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        color: var(--white);
    }
    .status-badge.pending { background-color: #f59e0b; } /* amber-500 */
    .status-badge.processing { background-color: #2563eb; } /* blue-600 */
    .status-badge.completed { background-color: #10b981; } /* emerald-500 */
    .status-badge.cancelled { background-color: #ef4444; } /* red-500 */

    /* Responsive Design */
    @media (max-width: 1200px) {
      .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      }
    }
    
    @media (max-width: 992px) {
      .admin-container {
        flex-direction: column;
      }
      
      .sidebar {
        position: fixed;
        left: -100%;
        transition: all 0.4s ease;
        z-index: 100;
        min-height: 100vh;
      }
      
      .sidebar.active {
        left: 0;
      }
      
      .main-content {
        margin-left: 0;
        padding: 1.5rem;
      }
      
      .menu-toggle {
        display: block;
      }
    }
    
    @media (max-width: 768px) {
      .header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
      }
      
      .user-info {
        align-self: flex-end;
      }
      
      .btn {
        width: 100%;
        justify-content: center;
      }
      
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .form-row {
        flex-direction: column;
        gap: 0;
      }
    }
    
    @media (max-width: 576px) {
      .main-content {
        padding: 1rem;
      }
      
      .stat-card {
        padding: 1.2rem;
      }
      
      .card {
        padding: 1.2rem;
      }
      
      .header h1 {
        font-size: 1.7rem;
      }
      
      .card-header h2 {
        font-size: 1.3rem;
      }
    }
  </style>
</head>
<body>
  <button class="menu-toggle" id="menuToggle">
    <i class="fas fa-bars"></i>
  </button>
  
  <div class="admin-container">
    <div class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <h2><i class="fas fa-laptop-code"></i> DigiTech Admin</h2>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li><a href="dashboard.php?page=dashboard" class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
          <li><a href="dashboard.php?page=users" class="<?php echo $current_page === 'users' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Users</a></li>
          <li><a href="dashboard.php?page=products" class="<?php echo $current_page === 'products' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i> Products</a></li>
          <li><a href="dashboard.php?page=orders" class="<?php echo $current_page === 'orders' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i> Orders</a></li>
          <li><a href="dashboard.php?page=reports" class="<?php echo $current_page === 'reports' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> Reports</a></li> <li><a href="?logout=1">
            <i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
      </nav>
    </div>
    
    <div class="main-content">
      <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle"></i>
          <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php endif; ?>
      
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i>
          <?php 
            // This is the switch statement for success messages
            switch($_GET['success']) {
              case 'product_added': echo 'Product added successfully!'; break;
              case 'product_updated': echo 'Product updated successfully!'; break;
              case 'product_deleted': echo 'Product deleted successfully!'; break;
              case 'user_updated': echo 'User updated successfully!'; break;
              case 'user_deleted': echo 'User deleted successfully!'; break;
              case 'order_updated': echo 'Order status updated successfully!'; break;
            }
          ?>
        </div>
      <?php endif; ?>
      
      <div class="header">
        <h1><i class="fas fa-<?php 
          // This is the switch statement for the header icon
          switch($current_page) {
            case 'dashboard': echo 'tachometer-alt'; break;
            case 'users': echo 'users'; break;
            case 'products': echo 'box'; break;
            case 'orders': echo 'shopping-cart'; break;
            case 'reports': echo 'chart-line'; break; // Added reports icon
            default: echo 'cogs'; // Default icon if page is not recognized
          }
        ?>"></i> <?php echo ucfirst($current_page); ?></h1>
        <div class="user-info">
          <div class="avatar">A</div>
          <div>
            <div>Admin User</div>
            <small>Administrator</small>
          </div>
        </div>
      </div>
      
      <?php if ($current_page === 'dashboard'): ?>
        <div class="stats-grid">
          <div class="stat-card users">
            <h3>Total Users</h3>
            <p><?php echo $user_count; ?></p>
          </div>
          <div class="stat-card active-users">
            <h3>Active Users (30 days)</h3>
            <p><?php echo $active_users; ?></p>
          </div>
          <div class="stat-card new-users">
            <h3>New Users (7 days)</h3>
            <p><?php echo $new_users; ?></p>
          </div>
        </div>
        
        <div class="card">
          <div class="card-header">
            <h2>Recent Products</h2>
            <button class="btn btn-primary" onclick="openModal('add-product')">
              <i class="fas fa-plus"></i> Add Product
            </button>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Product</th>
                  <th>Price</th>
                  <th>Category</th>
                  <th>Stock</th>
                  <th>Image</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (array_slice($products, 0, 5) as $product): ?>
                <tr>
                  <td><?php echo $product['id']; ?></td>
                  <td><?php echo htmlspecialchars($product['name']); ?></td>
                  <td>KSh <?php echo number_format($product['price']); ?></td>
                  <td><?php echo htmlspecialchars($product['category']); ?></td>
                  <td><?php echo $product['stock']; ?></td>
                  <td>
                    <?php if (!empty($product['image_url'])): ?>
                      <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="Product" class="product-image">
                    <?php else: ?>
                      <div class="product-image">No Image</div>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      
      <?php elseif ($current_page === 'users'): ?>
        <div class="card">
          <div class="card-header">
            <h2>User Management</h2>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Last Login</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $user): ?>
                <tr data-id="<?php echo $user['id']; ?>">
                  <td><?php echo $user['id']; ?></td>
                  <td><?php echo htmlspecialchars($user['name']); ?></td>
                  <td><?php echo htmlspecialchars($user['email']); ?></td>
                  <td><?php echo htmlspecialchars($user['role']); ?></td>
                  <td><?php echo $user['last_login'] ? date('M j, Y g:i a', strtotime($user['last_login'])) : 'Never'; ?></td>
                  <td>
                    <button class="btn btn-primary btn-sm" onclick="openModal('edit-user', <?php echo $user['id']; ?>)">
                      <i class="fas fa-edit"></i> Edit
                    </button>
                    <form method="POST" style="display: inline-block;">
                      <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                      <button type="submit" name="delete_user" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?')">
                        <i class="fas fa-trash"></i> Delete
                      </button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      
      <?php elseif ($current_page === 'products'): ?>
        <div class="card">
          <div class="card-header">
            <h2>Product Management</h2>
            <button class="btn btn-primary" onclick="openModal('add-product')">
              <i class="fas fa-plus"></i> Add Product
            </button>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Price</th>
                  <th>Category</th>
                  <th>Stock</th>
                  <th>Image</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($products as $product): ?>
                <tr data-id="<?php echo $product['id']; ?>"
                    data-name="<?php echo htmlspecialchars($product['name']); ?>"
                    data-price="<?php echo $product['price']; ?>"
                    data-original_price="<?php echo $product['original_price']; ?>"
                    data-category="<?php echo htmlspecialchars($product['category']); ?>"
                    data-stock="<?php echo $product['stock']; ?>"
                    data-description="<?php echo htmlspecialchars($product['description']); ?>"
                    data-image_url="<?php echo htmlspecialchars($product['image_url']); ?>">
                  <td><?php echo $product['id']; ?></td>
                  <td><?php echo htmlspecialchars($product['name']); ?></td>
                  <td>KSh <?php echo number_format($product['price']); ?></td>
                  <td><?php echo htmlspecialchars($product['category']); ?></td>
                  <td><?php echo $product['stock']; ?></td>
                  <td>
                    <?php if (!empty($product['image_url'])): ?>
                      <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="Product" class="product-image">
                    <?php else: ?>
                      <div class="product-image">No Image</div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <button class="btn btn-primary btn-sm" onclick="openModal('edit-product', <?php echo $product['id']; ?>)">
                      <i class="fas fa-edit"></i> Edit
                    </button>
                    <form method="POST" style="display: inline-block;">
                      <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                      <button type="submit" name="delete_product" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?')">
                        <i class="fas fa-trash"></i> Delete
                      </button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      
      <?php elseif ($current_page === 'orders'): ?>
         <div class="card" style="margin-bottom: 1.5rem; padding: 1rem; box-shadow: none; border: 1px solid var(--medium-gray);">
        <form action="dashboard.php" method="GET" class="form-row" style="align-items: flex-end;">
            <input type="hidden" name="page" value="orders">
            <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                <label for="order_start_date">Start Date:</label>
                <input type="date" id="order_start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($_GET['start_date'] ?? ''); ?>">
            </div>
            <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                <label for="order_end_date">End Date:</label>
                <input type="date" id="order_end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($_GET['end_date'] ?? ''); ?>">
            </div>
            <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                <label for="order_status">Status:</label>
                <select id="order_status" name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo (isset($_GET['status']) && $_GET['status'] === 'processing') ? 'selected' : ''; ?>>Processing</option>
                    <option value="completed" <?php echo (isset($_GET['status']) && $_GET['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo (isset($_GET['status']) && $_GET['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="form-group" style="flex: none; margin-bottom: 0;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply Filter</button>
                <a href="dashboard.php?page=orders" class="btn btn-primary" style="background-color: var(--dark-gray); margin-left: 0.5rem;"><i class="fas fa-redo"></i> Reset</a>
            </div>
        </form>
    </div>
        <div class="card">
          <div class="card-header">
            <h2>Order Management</h2>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Date</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders_data as $order): // Using orders_data here ?>
                <tr data-id="<?php echo $order['order_id']; ?>" data-status="<?php echo $order['status']; ?>">
                  <td><?php echo $order['order_id']; ?></td>
                  <td><?php echo htmlspecialchars($order['name']); ?></td>
                  <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td> <td>KSh <?php echo number_format($order['total']); ?></td>
                  <td>
                    <span class="status-badge" style="background-color: 
                      <?php 
                        // This is the switch statement for order status badge color
                        switch($order['status']) {
                          case 'pending': echo '#f59e0b'; break; // Warning
                          case 'processing': echo '#2563eb'; break; // Primary
                          case 'completed': echo '#10b981'; break; // Success
                          case 'cancelled': echo '#ef4444'; break; // Danger
                          default: echo '#6b7280'; break; // Dark Gray
                        }
                      ?>; color: var(--white);">
                      <?php echo ucfirst($order['status']); ?>
                    </span>
                  </td>
                  <td>
                    <button class="btn btn-primary btn-sm" onclick="openModal('edit-order-status', <?php echo $order['order_id']; ?>)">
                      <i class="fas fa-edit"></i> Update Status
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      <?php elseif ($current_page === 'reports'): ?>
        <div class="stats-grid">
          <div class="stat-card revenue"> 
            <h3>Total Revenue (Completed Orders)</h3>
            <p>KSh <?php echo number_format($total_revenue ?? 0, 2); ?></p>
            
          </div>
          <div class="stat-card revenue"> <h3>Number of Completed Orders</h3>
            <p><?php echo count($recent_completed_orders_report); ?></p>
          </div>
          <div class="stat-card users"> <h3>Total Inventory Value</h3>
            <p>KSh <?php echo number_format($total_inventory_value, 2); ?></p>
          </div>
        </div>
        <div class="card" style="margin-top: 1rem; padding: 1rem; box-shadow: none; border: 1px solid var(--medium-gray);">
                <form action="dashboard.php" method="GET" class="form-row" style="align-items: flex-end;">
                    <input type="hidden" name="page" value="reports">
                    <div class="form-group" style="flex: 1; margin-bottom: 0;">
                        <label for="start_date">Start Date:</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date ?? ''); ?>">
                    </div>
                    <div class="form-group" style="flex: 1; margin-bottom: 0;">
                        <label for="end_date">End Date:</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date ?? ''); ?>">
                    </div>
                    <div class="form-group" style="flex: none; margin-bottom: 0;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply Filter</button>
                        <a href="dashboard.php?page=reports" class="btn btn-primary" style="background-color: var(--dark-gray); margin-left: 0.5rem;"><i class="fas fa-redo"></i> Reset</a>
                    </div>
                </form>
            </div>

        <div class="card">
          <div class="card-header">
            <h2>Recent Completed Orders 
                <?php 
                    $date_range_text = '';
                    if ($start_date && $end_date) {
                        $date_range_text = ' (from ' . htmlspecialchars($start_date) . ' to ' . htmlspecialchars($end_date) . ')';
                    } elseif ($start_date) {
                        $date_range_text = ' (from ' . htmlspecialchars($start_date) . ')';
                    } elseif ($end_date) {
                        $date_range_text = ' (up to ' . htmlspecialchars($end_date) . ')';
                    }
                    echo $date_range_text;
                ?>
            </h2>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Date</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $current_date = '';
                $daily_subtotal = 0;
                if (!empty($recent_completed_orders_report)): 
                    foreach ($recent_completed_orders_report as $order):
                        $order_date = date('Y-m-d', strtotime($order['completed_at'] ?? $order['updated_at']));
                        // Check if the date has changed
                        if ($order_date != $current_date && $current_date != '') :
                            // Display subtotal for the previous day
                            echo '<tr>';
                            echo '<td colspan="3" style="text-align: right; font-weight: bold;">Daily Subtotal for ' . date('M j, Y', strtotime($current_date)) . ':</td>';
                            echo '<td style="font-weight: bold;">KSh ' . number_format($daily_subtotal, 2) . '</td>';
                            echo '</tr>';
                            $daily_subtotal = 0; // Reset subtotal for the new day
                        endif;
                        // Update current date
                        $current_date = $order_date;
                        $daily_subtotal += $order['total'];
                ?>
                  <tr>
                    <td><?php echo $order['order_id']; ?></td>
                    <td><?php echo htmlspecialchars($order['name']); ?></td>
                    <td><?php echo date('M j, Y', strtotime($order['completed_at'] ?? $order['updated_at'])); ?></td>
                    <td>KSh <?php echo number_format($order['total'], 2); ?></td>
                  </tr>
                <?php 
                    endforeach; 
                    // Display subtotal for the last day after the loop finishes
                    if ($current_date != '') :
                        echo '<tr>';
                        echo '<td colspan="3" style="text-align: right; font-weight: bold;">Daily Subtotal for ' . date('M j, Y', strtotime($current_date)) . ':</td>';
                        echo '<td style="font-weight: bold;">KSh ' . number_format($daily_subtotal, 2) . '</td>';
                        echo '</tr>';
                    endif;
                else: ?>
                  <tr><td colspan="4">No completed orders to display for the selected period.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h2>Low Stock Products</h2>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Product ID</th>
                  <th>Product Name</th>
                  <th>Current Stock</th>
                  <th>Price</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($low_stock_products)): ?>
                  <?php foreach ($low_stock_products as $product): ?>
                  <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><span class="status-badge" style="background-color: <?php echo $product['stock'] < 5 ? '#ef4444' : '#f59e0b'; ?>; color: var(--white);"><?php echo $product['stock']; ?></span></td>
                    <td>KSh <?php echo number_format($product['price'], 2); ?></td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="4">No products are currently low in stock.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div id="add-product" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Product</h3>
                <span class="close-btn" onclick="closeModal('add-product')">&times;</span>
            </div>
            <form action="dashboard.php" method="POST">
                <div class="form-group">
                    <label for="add_name">Product Name</label>
                    <input type="text" id="add_name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="add_description">Description</label>
                    <textarea id="add_description" name="description" class="form-control"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="add_price">Price (KSh)</label>
                        <input type="number" id="add_price" name="price" step="0.01" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="add_original_price">Original Price (KSh)</label>
                        <input type="number" id="add_original_price" name="original_price" step="0.01" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="add_category">Category</label>
                        <select id="add_category" name="category" class="form-control" required>
                            <option value="">Select Category</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Accessories">Accessories</option>
                            <option value="Components">Components</option>
                            <option value="Software">Software</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="add_stock">Stock Quantity</label>
                        <input type="number" id="add_stock" name="stock" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="add_image_url">Image URL</label>
                    <input type="text" id="add_image_url" name="image_url" class="form-control">
                    <div class="preview-container">
                        <img id="add-image-preview" class="preview-image" src="" alt="Image Preview">
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" class="btn" onclick="closeModal('add-product')">Cancel</button>
                    <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-product" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Product</h3>
                <span class="close-btn" onclick="closeModal('edit-product')">&times;</span>
            </div>
            <form action="dashboard.php" method="POST">
                <input type="hidden" id="edit_product_id" name="product_id">
                <div class="form-group">
                    <label for="edit_name">Product Name</label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" class="form-control"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_price">Price (KSh)</label>
                        <input type="number" id="edit_price" name="price" step="0.01" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_original_price">Original Price (KSh)</label>
                        <input type="number" id="edit_original_price" name="original_price" step="0.01" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_category">Category</label>
                        <select id="edit_category" name="category" class="form-control" required>
                            <option value="">Select Category</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Accessories">Accessories</option>
                            <option value="Components">Components</option>
                            <option value="Software">Software</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_stock">Stock Quantity</label>
                        <input type="number" id="edit_stock" name="stock" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit_image_url">Image URL</label>
                    <input type="text" id="edit_image_url" name="image_url" class="form-control">
                    <div class="preview-container">
                        <img id="edit-image-preview" class="preview-image" src="" alt="Image Preview">
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" class="btn" onclick="closeModal('edit-product')">Cancel</button>
                    <button type="submit" name="update_product" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-user" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit User</h3>
                <span class="close-btn" onclick="closeModal('edit-user')">&times;</span>
            </div>
            <form action="dashboard.php" method="POST">
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="form-group">
                    <label for="edit_username">Name</label>
                    <input type="text" id="edit_username" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_user_email">Email</label>
                    <input type="email" id="edit_user_email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_user_role">Role</label>
                    <select id="edit_user_role" name="role" class="form-control" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" class="btn" onclick="closeModal('edit-user')">Cancel</button>
                    <button type="submit" name="update_user" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-order-status" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Order Status</h3>
                <span class="close-btn" onclick="closeModal('edit-order-status')">&times;</span>
            </div>
            <form action="dashboard.php" method="POST">
                <input type="hidden" id="edit_order_id" name="order_id">
                <div class="form-group">
                    <label for="edit_order_status">Status</label>
                    <select id="edit_order_status" name="status" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" class="btn" onclick="closeModal('edit-order-status')">Cancel</button>
                    <button type="submit" name="update_order_status" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Universal modal opener
        function openModal(modalId, itemId = null) {
            const modal = document.getElementById(modalId);
            modal.classList.add('active');

            if (modalId === 'edit-product' && itemId) {
                const row = document.querySelector(`tr[data-id="${itemId}"]`);
                if (row) {
                    document.getElementById('edit_product_id').value = itemId;
                    document.getElementById('edit_name').value = row.dataset.name;
                    document.getElementById('edit_description').value = row.dataset.description;
                    document.getElementById('edit_price').value = row.dataset.price;
                    document.getElementById('edit_original_price').value = row.dataset.original_price;
                    document.getElementById('edit_category').value = row.dataset.category;
                    document.getElementById('edit_stock').value = row.dataset.stock;
                    document.getElementById('edit_image_url').value = row.dataset.image_url;

                    // Update image preview for edit modal
                    if (row.dataset.image_url) {
                        document.getElementById('edit-image-preview').src = row.dataset.image_url;
                        document.getElementById('edit-image-preview').style.display = 'block';
                    } else {
                        document.getElementById('edit-image-preview').src = '';
                        document.getElementById('edit-image-preview').style.display = 'none';
                    }
                }
            } else if (modalId === 'edit-user' && itemId) {
                const row = document.querySelector(`tr[data-id="${itemId}"]`);
                if (row) {
                    document.getElementById('edit_user_id').value = itemId;
                    // Assuming 'name' is the column for username in user table
                    document.getElementById('edit_username').value = row.cells[1].innerText; 
                    document.getElementById('edit_user_email').value = row.cells[2].innerText;
                    document.getElementById('edit_user_role').value = row.cells[3].innerText.toLowerCase();
                }
            } else if (modalId === 'edit-order-status' && itemId) {
                const row = document.querySelector(`tr[data-id="${itemId}"]`);
                if (row) {
                    document.getElementById('edit_order_id').value = itemId;
                    document.getElementById('edit_order_status').value = row.dataset.status;
                }
            }
        }

        // Universal modal closer
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            // Reset image previews when closing modals
            if (modalId === 'add-product') {
                document.getElementById('add-image-preview').src = '';
                document.getElementById('add-image-preview').style.display = 'none';
                document.getElementById('add_image_url').value = '';
            } else if (modalId === 'edit-product') {
                document.getElementById('edit-image-preview').src = '';
                document.getElementById('edit-image-preview').style.display = 'none';
                document.getElementById('edit_image_url').value = '';
            }
             // Clear form inputs
            const form = document.getElementById(modalId).querySelector('form');
            if (form) {
                form.reset();
            }
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modals = document.querySelectorAll('.modal.active');
            modals.forEach(modal => {
                // Ensure the click was directly on the modal backdrop, not its content
                if (event.target === modal) {
                    closeModal(modal.id);
                }
            });
        });

        // Image preview for add product modal
        const addImageUrlInput = document.getElementById('add_image_url');
        const addImagePreview = document.getElementById('add-image-preview');
        if (addImageUrlInput) {
            addImageUrlInput.addEventListener('input', function() {
                if (this.value) {
                    addImagePreview.src = this.value;
                    addImagePreview.style.display = 'block';
                } else {
                    addImagePreview.src = '';
                    addImagePreview.style.display = 'none';
                }
            });
        }
        
        // Image preview for edit product modal
        const editImageUrlInput = document.getElementById('edit_image_url');
        const editImagePreview = document.getElementById('edit-image-preview');
        if (editImageUrlInput) {
            editImageUrlInput.addEventListener('input', function() {
                if (this.value) {
                    editImagePreview.src = this.value;
                    editImagePreview.style.display = 'block';
                } else {
                    editImagePreview.src = '';
                    editImagePreview.style.display = 'none';
                }
            });
        }

        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        menuToggle.addEventListener('click', function() {
          sidebar.classList.toggle('active');
          // Adjust main content margin when sidebar toggles on mobile
          if (window.innerWidth <= 992) {
              if (sidebar.classList.contains('active')) {
                  menuToggle.innerHTML = '<i class="fas fa-times"></i>';
              } else {
                  menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
              }
          }
        });

        // Handle sidebar behavior on resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                sidebar.classList.remove('active');
                menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            }
        });

        // Initialize menu state on larger screens if needed
        if (window.innerWidth > 992) {
            sidebar.classList.remove('active'); // Ensure sidebar is not active on large screens by default
        }
    </script>
</body>
</html>