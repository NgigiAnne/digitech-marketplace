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

// Get current page from URL or default to dashboard
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

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

// Get statistics and data
$user_count = getCount($conn, "SELECT COUNT(*) FROM users");
$active_users = getCount($conn, "SELECT COUNT(*) FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 30 DAY)");
$new_users = getCount($conn, "SELECT COUNT(*) FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
$products = getData($conn, "SELECT * FROM products");
$users = ($current_page === 'users') ? getData($conn, "SELECT * FROM users") : [];
$orders = ($current_page === 'orders') ? getData($conn, "SELECT orders.*, users.name FROM orders JOIN users ON orders.user_id = users.id") : [];

// Handle form submissions
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Add new product
        if (isset($_POST['add_product'])) {
            // Validate required fields
            $required = ['name', 'price', 'category', 'stock', 'description'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("$field is required");
                }
            }

            // Prepare statement with correct columns (6 values)
            $stmt = $conn->prepare("INSERT INTO products (name, price, original_price, category, stock, description) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            // Set defaults for optional fields
            $original_price = $_POST['original_price'] ?? $_POST['price'];
            $description = $_POST['description'] ?? '';

            // Bind parameters with correct types: 
            // name (s), price (d), original_price (d), category (s), stock (i), description (s)
            $stmt->bind_param("sddisss", 
                $_POST['name'],
                $_POST['price'],
                $original_price,
                $_POST['category'],
                $_POST['stock'],  // Now correctly bound as integer (i)
                $description,     // Now correctly bound as string (s)
                $image_url
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            header("Location: admin.php?page=products&success=product_added");
            exit();
        }
        // Update product
        elseif (isset($_POST['update_product'])) {
            $required = ['name', 'price', 'category', 'stock', 'product_id'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("$field is required");
                }
            }

            $stmt = $conn->prepare("UPDATE products SET 
                                  name = ?, 
                                  price = ?, 
                                  original_price = ?, 
                                  description = ?, 
                                  category = ?, 
                                  stock = ?
                                  WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            // Set defaults for optional fields
            $original_price = $_POST['original_price'] ?? $_POST['price'];
            $description = $_POST['description'] ?? '';
            
            $stmt->bind_param("sddisssi",
                $_POST['name'],
                $_POST['price'], 
                $original_price,
                $_POST['category'],
                $_POST['stock'],  // Corrected to integer (i)
                $description,     // Corrected to string (s)
                $image_url,
                $_POST['product_id']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            header("Location: admin.php?page=products&success=product_updated");
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
            
            header("Location: admin.php?page=products&success=product_deleted");
            exit();
        }
        // Update user
        elseif (isset($_POST['update_user'])) {
            $required = ['username', 'email', 'role', 'user_id'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("$field is required");
                }
            }

            $stmt = $conn->prepare("UPDATE users SET 
                                  username = ?, 
                                  email = ?, 
                                  role = ?
                                  WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("sssi",
                $_POST['username'],
                $_POST['email'],
                $_POST['role'],
                $_POST['user_id']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            header("Location: admin.php?page=users&success=user_updated");
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
            
            header("Location: admin.php?page=users&success=user_deleted");
            exit();
        }
        // Update order status
        elseif (isset($_POST['update_order_status'])) {
            $required = ['status', 'order_id'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("$field is required");
                }
            }

            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("si",
                $_POST['status'],
                $_POST['order_id']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            header("Location: admin.php?page=orders&success=order_updated");
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
    }
    
    .admin-container {
      display: grid;
      grid-template-columns: 250px 1fr;
      min-height: 100vh;
    }
    
    .sidebar {
      background-color: #1e3a8a;
      color: var(--white);
      padding: 1.5rem;
    }
    
    .sidebar-header {
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid rgba(255,255,255,0.1);
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
      display: block;
      padding: 0.5rem;
      border-radius: 4px;
      transition: all 0.3s ease;
    }
    
    .sidebar-nav a:hover, 
    .sidebar-nav a.active {
      color: var(--white);
      background-color: rgba(255,255,255,0.1);
    }
    
    .sidebar-nav i {
      width: 24px;
      text-align: center;
      margin-right: 0.5rem;
    }
    
    .main-content {
      padding: 2rem;
    }
    
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    
    .stat-card {
      background-color: var(--white);
      border-radius: 8px;
      padding: 1.5rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .stat-card h3 {
      font-size: 0.9rem;
      color: var(--dark-gray);
      margin-bottom: 0.5rem;
    }
    
    .stat-card p {
      font-size: 1.75rem;
      font-weight: 700;
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
    
    .card {
      background-color: var(--white);
      border-radius: 8px;
      padding: 1.5rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      margin-bottom: 2rem;
    }
    
    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--medium-gray);
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
    }
    
    th, td {
      padding: 0.75rem 1rem;
      text-align: left;
      border-bottom: 1px solid var(--medium-gray);
    }
    
    th {
      font-weight: 600;
      color: var(--dark-gray);
    }
    
    .btn {
      padding: 0.5rem 1rem;
      border-radius: 4px;
      border: none;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    .btn-primary {
      background-color: var(--primary-color);
      color: var(--white);
    }
    
    .btn-primary:hover {
      background-color: var(--primary-dark);
    }
    
    .btn-danger {
      background-color: var(--danger-color);
      color: var(--white);
    }
    
    .btn-danger:hover {
      background-color: #dc2626;
    }
    
    .btn-sm {
      padding: 0.25rem 0.5rem;
      font-size: 0.8rem;
    }
    
    .form-group {
      margin-bottom: 1rem;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
    }
    
    .form-control {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid var(--medium-gray);
      border-radius: 4px;
      font-size: 1rem;
    }
    
    /* Compact Modal Styles */
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
    }
    
    .modal.active {
      display: flex;
    }
    
    .modal-content {
      background-color: var(--white);
      border-radius: 8px;
      width: 90%;
      max-width: 380px;
      padding: 1.2rem;
      box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }
    
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.8rem;
      padding-bottom: 0.5rem;
      border-bottom: 1px solid #e3e6f0;
    }
    
    .close-btn {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: #5a5c69;
    }
    
    .form-row {
      display: flex;
      gap: 0.8rem;
    }
    
    .alert {
      padding: 1rem;
      border-radius: 4px;
      margin-bottom: 1rem;
    }
    
    .alert-success {
      background-color: #d1fae5;
      color: #065f46;
    }
    
    .alert-danger {
      background-color: #fee2e2;
      color: #991b1b;
    }
  </style>
</head>
<body>
  <div class="admin-container">
    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-header">
        <h2>DigiTech Admin</h2>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li><a href="?page=dashboard" class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
          <li><a href="?page=users" class="<?php echo $current_page === 'users' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Users</a></li>
          <li><a href="?page=products" class="<?php echo $current_page === 'products' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i> Products</a></li>
          <li><a href="?page=orders" class="<?php echo $current_page === 'orders' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i> Orders</a></li>
          <li><a href="?logout=1">
            <i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
      </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
      <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
          <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php endif; ?>
      
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
          <?php 
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
        <h1><?php echo ucfirst($current_page); ?></h1>
        <div>
          <span>Welcome, Admin</span>
        </div>
      </div>
      
      <?php if ($current_page === 'dashboard'): ?>
        <!-- Dashboard Content -->
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
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
                <th>Category</th>
                <th>Stock</th>
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
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      
      <?php elseif ($current_page === 'users'): ?>
        <!-- Users Content -->
        <div class="card">
          <div class="card-header">
            <h2>User Management</h2>
          </div>
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
      
      <?php elseif ($current_page === 'products'): ?>
        <!-- Products Content -->
        <div class="card">
          <div class="card-header">
            <h2>Product Management</h2>
            <button class="btn btn-primary" onclick="openModal('add-product')">
              <i class="fas fa-plus"></i> Add Product
            </button>
          </div>
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
                <th>Category</th>
                <th>Stock</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $product): ?>
              <tr data-id="<?php echo $product['id']; ?>">
                <td><?php echo $product['id']; ?></td>
                <td><?php echo htmlspecialchars($product['name']); ?></td>
                <td>KSh <?php echo number_format($product['price']); ?></td>
                <td><?php echo htmlspecialchars($product['category']); ?></td>
                <td><?php echo $product['stock']; ?></td>
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
      
      <?php elseif ($current_page === 'orders'): ?>
        <!-- Orders Content -->
        <div class="card">
          <div class="card-header">
            <h2>Order Management</h2>
          </div>
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
              <?php foreach ($orders as $order): ?>
              <tr data-id="<?php echo $order['id']; ?>">
                <td><?php echo $order['id']; ?></td>
                <td><?php echo htmlspecialchars($order['name']); ?></td>
                <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                <td>KSh <?php echo number_format($order['total_amount']); ?></td>
                <td><?php echo htmlspecialchars($order['status']); ?></td>
                <td>
                  <button class="btn btn-primary btn-sm" onclick="openModal('edit-order', <?php echo $order['id']; ?>)">
                    <i class="fas fa-edit"></i> Update Status
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- Compact Add Product Modal -->
  <div class="modal" id="add-product-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 style="font-size: 1.2rem;">Add Product</h3>
        <button class="close-btn" onclick="closeModal()">&times;</button>
      </div>
      <form method="POST" action="admin.php?page=<?php echo $current_page; ?>">
        <input type="hidden" name="add_product" value="1">
        <div class="form-group">
          <label for="name" style="font-size: 0.9rem;">Name</label>
          <input type="text" id="name" name="name" class="form-control" required>
        </div>
        <div class="form-row">
          <div class="form-group" style="flex: 1;">
            <label for="price" style="font-size: 0.9rem;">Price</label>
            <input type="number" id="price" name="price" class="form-control" step="0.01" required>
          </div>
          <div class="form-group" style="flex: 1;">
            <label for="original_price" style="font-size: 0.9rem;">Original Price</label>
            <input type="number" id="original_price" name="original_price" class="form-control" step="0.01" required>
          </div>
        </div>
        <div class="form-group">
          <label for="category" style="font-size: 0.9rem;">Category</label>
          <select id="category" name="category" class="form-control" required style="padding: 0.5rem 0.6rem;">
            <option value="Electronics">Electronics</option>
            <option value="Computing">Computing</option>
            <option value="Phones">Phones</option>
            <option value="Accessories">Accessories</option>
          </select>
        </div>
        <div class="form-group">
          <label for="stock" style="font-size: 0.9rem;">Stock</label>
          <input type="number" id="stock" name="stock" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="description" style="font-size: 0.9rem;">Description</label>
          <textarea id="description" name="description" class="form-control" rows="2"></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary" style="margin-top: 0.8rem;">
          <i class="fas fa-save"></i> Save
        </button>
      </form>
    </div>
  </div>
  
  <!-- Compact Edit Product Modal -->
  <div class="modal" id="edit-product-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 style="font-size: 1.2rem;">Edit Product</h3>
        <button class="close-btn" onclick="closeModal()">&times;</button>
      </div>
      <form method="POST" action="admin.php?page=<?php echo $current_page; ?>">
        <input type="hidden" name="update_product" value="1">
        <input type="hidden" id="edit_product_id" name="product_id">
        <div class="form-group">
          <label for="edit_name" style="font-size: 0.9rem;">Name</label>
          <input type="text" id="edit_name" name="name" class="form-control" required>
        </div>
        <div class="form-row">
          <div class="form-group" style="flex: 1;">
            <label for="edit_price" style="font-size: 0.9rem;">Price</label>
            <input type="number" id="edit_price" name="price" class="form-control" step="0.01" required>
          </div>
          <div class="form-group" style="flex: 1;">
            <label for="edit_original_price" style="font-size: 0.9rem;">Original Price</label>
            <input type="number" id="edit_original_price" name="original_price" class="form-control" step="0.01" required>
          </div>
        </div>
        <div class="form-group">
          <label for="edit_category" style="font-size: 0.9rem;">Category</label>
          <select id="edit_category" name="category" class="form-control" required style="padding: 0.5rem 0.6rem;">
            <option value="Electronics">Electronics</option>
            <option value="Computing">Computing</option>
            <option value="Phones">Phones</option>
            <option value="Accessories">Accessories</option>
          </select>
        </div>
        <div class="form-group">
          <label for="edit_stock" style="font-size: 0.9rem;">Stock</label>
          <input type="number" id="edit_stock" name="stock" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="edit_description" style="font-size: 0.9rem;">Description</label>
          <textarea id="edit_description" name="description" class="form-control" rows="2"></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary" style="margin-top: 0.8rem;">
          <i class="fas fa-save"></i> Update
        </button>
      </form>
    </div>
  </div>
  
  <!-- Edit User Modal -->
  <div class="modal" id="edit-user-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 style="font-size: 1.2rem;">Edit User</h3>
        <button class="close-btn" onclick="closeModal()">&times;</button>
      </div>
      <form method="POST" action="admin.php?page=<?php echo $current_page; ?>">
        <input type="hidden" name="update_user" value="1">
        <input type="hidden" id="edit_user_id" name="user_id">
        <div class="form-group">
          <label for="edit_username" style="font-size: 0.9rem;">Username</label>
          <input type="text" id="edit_username" name="username" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="edit_email" style="font-size: 0.9rem;">Email</label>
          <input type="email" id="edit_email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="edit_role" style="font-size: 0.9rem;">Role</label>
          <select id="edit_role" name="role" class="form-control" required style="padding: 0.5rem 0.6rem;">
            <option value="user">User</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top: 0.8rem;">
          <i class="fas fa-save"></i> Update
        </button>
      </form>
    </div>
  </div>
  
  <!-- Edit Order Modal -->
  <div class="modal" id="edit-order-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 style="font-size: 1.2rem;">Update Order Status</h3>
        <button class="close-btn" onclick="closeModal()">&times;</button>
      </div>
      <form method="POST" action="admin.php?page=<?php echo $current_page; ?>">
        <input type="hidden" name="update_order_status" value="1">
        <input type="hidden" id="edit_order_id" name="order_id">
        <div class="form-group">
          <label for="edit_status" style="font-size: 0.9rem;">Status</label>
          <select id="edit_status" name="status" class="form-control" required style="padding: 0.5rem 0.6rem;">
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top: 0.8rem;">
          <i class="fas fa-save"></i> Update Status
        </button>
      </form>
    </div>
  </div>
  
  <script>
    function openModal(modalType, id = null) {
      if (modalType === 'add-product') {
        document.getElementById('add-product-modal').classList.add('active');
      } 
      else if (modalType === 'edit-product' && id) {
        const productRow = document.querySelector(`tr[data-id="${id}"]`);
        if (productRow) {
          document.getElementById('edit_product_id').value = id;
          document.getElementById('edit_name').value = productRow.querySelector('td:nth-child(2)').textContent;
          document.getElementById('edit_price').value = productRow.querySelector('td:nth-child(3)').textContent.replace('KSh ', '').replace(',', '');
          document.getElementById('edit_category').value = productRow.querySelector('td:nth-child(4)').textContent;
          document.getElementById('edit_stock').value = productRow.querySelector('td:nth-child(5)').textContent;
          document.getElementById('edit-product-modal').classList.add('active');
        }
      }
      else if (modalType === 'edit-user' && id) {
        const userRow = document.querySelector(`tr[data-id="${id}"]`);
        if (userRow) {
          document.getElementById('edit_user_id').value = id;
          document.getElementById('edit_username').value = userRow.querySelector('td:nth-child(2)').textContent;
          document.getElementById('edit_email').value = userRow.querySelector('td:nth-child(3)').textContent;
          document.getElementById('edit_role').value = userRow.querySelector('td:nth-child(4)').textContent.toLowerCase();
          document.getElementById('edit-user-modal').classList.add('active');
        }
      }
      else if (modalType === 'edit-order' && id) {
        const orderRow = document.querySelector(`tr[data-id="${id}"]`);
        if (orderRow) {
          document.getElementById('edit_order_id').value = id;
          document.getElementById('edit_status').value = orderRow.querySelector('td:nth-child(5)').textContent.toLowerCase();
          document.getElementById('edit-order-modal').classList.add('active');
        }
      }
    }
    
    function closeModal() {
      document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('active');
      });
    }
  </script>
</body>
</html>