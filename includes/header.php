<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/auth.php';
$loggedIn = is_logged_in();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DigiTech Marketplace - Premium electronics with exclusive discounts">
    <title><?php echo isset($page_title) ? $page_title : 'DigiTech Marketplace'; ?></title>
    
    <!-- CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    
    <!-- Favicon -->
    <link rel="icon" href="../assets/images/favicon.ico" type="image/x-icon">
</head>
<body class="<?php echo $loggedIn ? 'user-logged-in' : ''; ?>">
    <header>
        <a href="../index.php" class="logo">
            <img src="https://i.postimg.cc/qBX7DSGZ/computer-retail-and-repair-shop-logo-commercial-logo-design-vector-template-W7-H37-M.jpg" alt="DigiTech Logo" style="height: 40px;">
            <span class="logo-text">DigiTech</span>
        </a>
        
        <button class="mobile-menu-toggle" aria-label="Toggle navigation">
            <i class="fas fa-bars"></i>
        </button>
        
        <nav class="main-nav">
            <a href="../user/dashboard.php">Home</a>
             <li><a href="../user/dashboard.php">All Products</a></li>
                    <li><a href="../user/dashboard.php">Categories</a></li>
                    
            <?php if ($loggedIn): ?>
                <a href="../logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
                <a href="../user/dashboard.php" class="account-link">
                    <i class="fas fa-user-circle"></i> My Account
                </a>
            <?php else: ?>
                <a href="../login.php" class="login-link">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            <?php endif; ?>
            
            <a href="#" class="cart-link" onclick="showCart()">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count" id="cart-count">0</span>
            </a>
        </nav>
        
        <div class="search-container">
            <form action="../search.php" method="GET">
                <input class="search-bar" type="text" name="q" placeholder="Search products..." id="searchInput">
                <button type="submit" class="search-btn" aria-label="Search">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </header>

    <main class="main">