<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
$loggedIn = is_logged_in();
if (!is_logged_in()) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="DigiTech Marketplace - Premium electronics with exclusive discounts">
  <title>DigiTech Marketplace</title>
  
  <!-- Preload critical resources -->
  <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" as="style">
  <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style">
  
  <!-- CSS -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  
  <!-- Favicon -->
  <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
  
  <style>
    :root {
  --primary-color: #2563eb;
  --primary-dark: #1e40af;
  --primary-light: #93c5fd;
  --secondary-color: #10b981;
  --danger-color: #ef4444;
  --warning-color: #f59e0b;
  --info-color: #3b82f6;
  --light-gray: #f9fafb;
  --medium-gray: #e5e7eb;
  --dark-gray: #6b7280;
  --dark-text: #1f2937;
  --white: #ffffff;
  --black: #000000;
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.1);
  --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
  --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.1);
  --transition: all 0.3s ease;
  --border-radius: 8px;
  --border-radius-lg: 12px;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

html {
  scroll-behavior: smooth;
}

body {
  font-family: 'Inter', sans-serif;
  background-color: var(--light-gray);
  color: var(--dark-text);
  line-height: 1.6;
  transition: var(--transition);
}

body.modal-open,
body.cart-open {
  overflow: hidden;
}

/* ===== Typography ===== */
h1, h2, h3, h4, h5, h6 {
  font-weight: 700;
  line-height: 1.2;
  margin-bottom: 1rem;
}

h1 { font-size: 2.5rem; }
h2 { font-size: 2rem; }
h3 { font-size: 1.75rem; }
h4 { font-size: 1.5rem; }

p {
  margin-bottom: 1rem;
}

a {
  color: var(--primary-color);
  text-decoration: none;
  transition: var(--transition);
}

a:hover {
  color: var(--primary-dark);
}

/* ===== Layout ===== */
.container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

.section {
  padding: 3rem 0;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

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

/* ===== Hero Section ===== */
.hero {
  background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
              url(https://i.postimg.cc/MKFkcQt5/photo-1518770660439-4636190af475.avif);
  color: var(--white);
  text-align: center;
  padding: 10rem 2rem;
  border-radius: var(--border-radius-lg);
  margin-bottom: 3rem;
  position: relative;
  overflow: hidden;
}

.hero::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.6);
  border-radius: var(--border-radius-lg);
}

.hero-content {
  position: relative;
  z-index: 1;
  max-width: 800px;
  margin: 0 auto;
}

.hero h1 {
  font-size: 3rem;
  margin-bottom: 1rem;
  line-height: 1.2;
}

.hero p {
  font-size: 1.4rem;
  margin-bottom: 2rem;
  opacity: 0.9;
}

 /* Updated Category Section Styles */
.categories-section {
  padding: 4rem 0;
  background: linear-gradient(to bottom, #f9fafb, #ffffff);
}

.section-header {
  text-align: center;
  margin-bottom: 3rem;
}

.section-title {
  font-size: 2.5rem;
  color: #1e40af;
  margin-bottom: 1rem;
  position: relative;
  display: inline-block;
}

.section-title::after {
  content: '';
  position: absolute;
  bottom: -10px;
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 4px;
  background: linear-gradient(to right, #2563eb, #10b981);
  border-radius: 2px;
}

.category-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

.category-card {
  position: relative;
  height: 350px;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
  cursor: pointer;
}

.category-card:hover {
  transform: translateY(-10px) scale(1.02);
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
}

.category-bg {
  position: absolute;
  width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center;
  z-index: 1;
  transition: transform 0.5s ease;
}

.category-card:hover .category-bg {
  transform: scale(1.1);
}

.category-overlay {
  position: absolute;
  width: 100%;
  height: 100%;
  background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.8));
  z-index: 2;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 2rem;
  color: white;
  transition: all 0.4s ease;
}

.category-card:hover .category-overlay {
  background: linear-gradient(to bottom, rgba(0,0,0,0.2), rgba(0,0,0,0.9));
}

.category-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  color: rgba(255, 255, 255, 0.9);
  transition: all 0.3s ease;
}

.category-card:hover .category-icon {
  transform: scale(1.1);
  color: #ffffff;
}

.category-name {
  font-size: 1.8rem;
  font-weight: 700;
  margin: 0;
  text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
  transition: all 0.3s ease;
}

.category-hover-content {
  max-height: 0;
  overflow: hidden;
  opacity: 0;
  transition: all 0.4s ease;
}

.category-card:hover .category-hover-content {
  max-height: 100px;
  opacity: 1;
  margin-top: 1rem;
}

.category-hover-content p {
  margin-bottom: 1rem;
  font-size: 1rem;
  line-height: 1.5;
}

.shop-now {
  display: inline-flex;
  align-items: center;
  font-weight: 600;
  color: #ffffff;
  padding: 0.5rem 1rem;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50px;
  transition: all 0.3s ease;
}

.shop-now i {
  margin-left: 0.5rem;
  transition: transform 0.3s ease;
}

.category-card:hover .shop-now {
  background: rgba(255, 255, 255, 0.3);
}

.category-card:hover .shop-now i {
  transform: translateX(5px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .category-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .category-card {
    height: 300px;
  }
  
  .section-title {
    font-size: 2rem;
  }
}
/* ===== Product Grid ===== */
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1.5rem;
}

.product-card {
  background-color: var(--white);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  transition: var(--transition);
  position: relative;
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-md);
}

.product-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background-color: var(--danger-color);
  color: var(--white);
  padding: 0.25rem 0.5rem;
  border-radius: var(--border-radius);
  font-size: 0.75rem;
  font-weight: 600;
}

.product-card img {
  width: 100%;
  height: 180px;
  object-fit: cover;
  margin-bottom: 1rem;
  border-radius: var(--border-radius);
}

.product-card h3 {
  font-size: 1.1rem;
  margin-bottom: 0.5rem;
  flex-grow: 1;
}

.price {
  color: var(--primary-color);
  font-weight: 700;
  font-size: 1.1rem;
}

.original-price {
  text-decoration: line-through;
  color: var(--dark-gray);
  font-size: 0.9rem;
  margin-left: 0.5rem;
}

.rating {
  margin: 0.5rem 0;
  color: var(--warning-color);
}

.review-count {
  color: var(--dark-gray);
  font-size: 0.8rem;
  margin-left: 0.5rem;
}

.stock-status {
  font-size: 0.85rem;
  margin: 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.in-stock {
  color: var(--secondary-color);
}

.low-stock {
  color: var(--warning-color);
}

.product-card button {
  background-color: var(--primary-color);
  color: var(--white);
  padding: 0.6rem;
  border: none;
  border-radius: var(--border-radius);
  margin-top: 0.5rem;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-weight: 600;
}

.product-card button:hover {
  background-color: var(--primary-dark);
}

.quick-view {
  background-color: var(--medium-gray) !important;
  color: var(--dark-text) !important;
  margin-top: 0.5rem !important;
}

.quick-view:hover {
  background-color: var(--dark-gray) !important;
  color: var(--white) !important;
}

/* ===== Sort & Filter ===== */
.sort-options {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.sort-options label {
  font-weight: 600;
}

.sort-options select {
  padding: 0.5rem;
  border-radius: var(--border-radius);
  border: 1px solid var(--medium-gray);
  background-color: var(--white);
}

/* ===== Pagination ===== */
.pagination {
  margin-top: 2rem;
  display: flex;
  justify-content: center;
}

.pagination-controls {
  display: flex;
  gap: 0.5rem;
}

.pagination-btn {
  padding: 0.5rem 1rem;
  border: 1px solid var(--medium-gray);
  background-color: var(--white);
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: var(--transition);
}

.pagination-btn:hover:not(.disabled) {
  background-color: var(--primary-color);
  color: var(--white);
  border-color: var(--primary-color);
}

.pagination-btn.active {
  background-color: var(--primary-color);
  color: var(--white);
  border-color: var(--primary-color);
}

.pagination-btn.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ===== Modals ===== */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0,0,0,0.5);
  z-index: 2000;
  display: flex;
  justify-content: center;
  align-items: center;
  opacity: 0;
  visibility: hidden;
  transition: var(--transition);
  backdrop-filter: blur(2px);
}

.modal-overlay.active {
  opacity: 1;
  visibility: visible;
}

.modal-container {
  background-color: var(--white);
  border-radius: var(--border-radius-lg);
  width: 90%;
  max-width: 400px;
  padding: 2rem;
  box-shadow: var(--shadow-xl);
  transform: translateY(20px);
  transition: var(--transition);
  max-height: 90vh;
  overflow-y: auto;
}

.modal-overlay.active .modal-container {
  transform: translateY(0);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.modal-title {
  font-size: 1.5rem;
  color: var(--dark-text);
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: var(--dark-gray);
  transition: var(--transition);
}

.close-btn:hover {
  color: var(--dark-text);
}

/* Tabs */
.tabs {
  display: flex;
  border-bottom: 1px solid var(--medium-gray);
  margin-bottom: 1.5rem;
}

.tab-btn {
  flex: 1;
  padding: 0.8rem;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  color: var(--dark-gray);
}

.tab-btn.active {
  border-bottom-color: var(--primary-color);
  color: var(--primary-color);
}

.tab-content {
  display: none;
}

.tab-content.active {
  display: block;
}



/* ===== Cart Sidebar ===== */
.cart-sidebar {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  width: 100%;
  max-width: 400px;
  background-color: var(--white);
  box-shadow: var(--shadow-xl);
  z-index: 2000;
  transform: translateX(100%);
  transition: var(--transition);
  display: flex;
  flex-direction: column;
}

.cart-sidebar.active {
  transform: translateX(0);
}

.cart-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--medium-gray);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.close-cart {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: var(--dark-gray);
}

.cart-items {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
}

.empty-cart {
  text-align: center;
  padding: 2rem;
  color: var(--dark-gray);
}

.cart-item {
  display: flex;
  gap: 1rem;
  padding: 1rem 0;
  border-bottom: 1px solid var(--medium-gray);
}

.cart-item:last-child {
  border-bottom: none;
}

.cart-item img {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: var(--border-radius);
}

.cart-item-details {
  flex: 1;
}

.cart-item h4 {
  font-size: 1rem;
  margin-bottom: 0.5rem;
}

.cart-item-price {
  color: var(--dark-gray);
  font-size: 0.9rem;
}

.cart-item-total {
  font-weight: 600;
  margin-top: 0.25rem;
}

.cart-item-actions {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.cart-item-actions button {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--dark-gray);
  transition: var(--transition);
  padding: 0.25rem;
}

.cart-item-actions button:hover {
  color: var(--primary-color);
}

.cart-item-actions span {
  font-weight: 600;
}

.cart-summary {
  padding: 1.5rem;
  border-top: 1px solid var(--medium-gray);
}

.cart-total {
  display: flex;
  justify-content: space-between;
  font-weight: 600;
  font-size: 1.1rem;
  margin-bottom: 1.5rem;
}

.checkout-btn {
  width: 100%;
  padding: 1rem;
  background-color: var(--primary-color);
  color: var(--white);
  border: none;
  border-radius: var(--border-radius);
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
}

.checkout-btn:hover {
  background-color: var(--primary-dark);
}

/* ===== Toast Notifications ===== */
.toast {
  position: fixed;
  bottom: 30px;
  left: 50%;
  transform: translateX(-50%) translateY(100px);
  background-color: var(--primary-color);
  color: var(--white);
  padding: 0.75rem 1.5rem;
  border-radius: var(--border-radius);
  opacity: 0;
  transition: var(--transition);
  z-index: 3000;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  max-width: 90%;
  box-shadow: var(--shadow-lg);
}

.toast.show {
  transform: translateX(-50%) translateY(0);
  opacity: 1;
}

.toast.success {
  background-color: var(--secondary-color);
}

.toast.error {
  background-color: var(--danger-color);
}

.toast.warning {
  background-color: var(--warning-color);
}

.toast.info {
  background-color: var(--info-color);
}

/* ===== Loading Spinner ===== */
.loading-spinner {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(255, 255, 255, 0.8);
  z-index: 3000;
  display: flex;
  justify-content: center;
  align-items: center;
  opacity: 0;
  visibility: hidden;
  transition: var(--transition);
}

.loading-spinner.active {
  opacity: 1;
  visibility: visible;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 5px solid var(--primary-light);
  border-top-color: var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

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

/* ===== Responsive Design ===== */
@media (max-width: 992px) {
  .hero {
    padding: 4rem 1.5rem;
  }
  
  .hero h1 {
    font-size: 2.5rem;
  }
  
  .hero p {
    font-size: 1.2rem;
  }
}

@media (max-width: 768px) {
  .mobile-menu-toggle {
    display: block;
  }
  
  .main-nav {
    position: fixed;
    top: 80px;
    left: 0;
    right: 0;
    background-color: var(--white);
    flex-direction: column;
    padding: 1.5rem;
    box-shadow: var(--shadow-md);
    transform: translateY(-150%);
    transition: var(--transition);
    z-index: 999;
  }
  
  .main-nav.active {
    transform: translateY(0);
  }
  
  .search-container {
    margin-top: 1rem;
    width: 100%;
  }
  
  .search-bar {
    width: 100%;
  }
  
  .search-bar:focus {
    width: 100%;
  }
  
  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .sort-options {
    width: 100%;
  }
  
  .sort-options select {
    flex-grow: 1;
  }
}

@media (max-width: 576px) {
  header {
    padding: 1rem;
    flex-wrap: wrap;
  }
  
  .logo {
    font-size: 1.5rem;
  }
  
  .hero {
    padding: 3rem 1rem;
    margin-bottom: 1.5rem;
  }
  
  .hero h1 {
    font-size: 2rem;
  }
  
  .hero p {
    font-size: 1rem;
  }
  
  .category-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .product-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .footer-content {
    grid-template-columns: 1fr;
  }
}

/* ===== Utility Classes ===== */
.no-results {
  text-align: center;
  padding: 2rem;
  grid-column: 1 / -1;
  color: var(--dark-gray);
}

.cta-button {
  background-color: var(--primary-color);
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  border-radius: 2rem;
  transition: var(--transition);
  border: none;
  color: var(--white);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.cta-button:hover {
  background-color: var(--primary-dark);
  transform: translateY(-3px);
  box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

/* Animation */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.fade-in {
  animation: fadeIn 0.5s ease-in-out;
}
/* Add this to your existing CSS */
.checkout-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
  margin-top: 2rem;
}

.checkout-form {
  background: var(--white);
  padding: 2rem;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-sm);
}

.order-summary {
  background: var(--white);
  padding: 2rem;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-sm);
  position: sticky;
  top: 1rem;
}

.order-items {
  margin: 1rem 0;
  max-height: 400px;
  overflow-y: auto;
}

.order-item {
  display: flex;
  gap: 1rem;
  padding: 1rem 0;
  border-bottom: 1px solid var(--medium-gray);
}

.order-item img {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: var(--border-radius);
}

.order-total {
  font-weight: 600;
  font-size: 1.2rem;
  text-align: right;
  margin-top: 1rem;
}

.confirmation-message {
  text-align: center;
  max-width: 800px;
  margin: 2rem auto;
  padding: 2rem;
  background: var(--white);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-sm);
}

.order-details {
  text-align: left;
  margin-top: 2rem;
  border-top: 1px solid var(--medium-gray);
  padding-top: 2rem;
}

@media (max-width: 768px) {
  .checkout-grid {
    grid-template-columns: 1fr;
  }
  
  .order-summary {
    position: static;
  }
}
/* Account Modal Styles - Enhanced */
.account-modal {
  position: fixed;
  top: 0;
  right: 0;
  width: 400px;
  max-width: 100%;
  height: 100vh;
  background: #f9f9f9;
  box-shadow: -4px 0 15px rgba(0, 0, 0, 0.2);
  z-index: 2000;
  transform: translateX(100%);
  transition: transform 0.4s ease;
  overflow-y: auto;
  border-top-left-radius: 20px;
  border-bottom-left-radius: 20px;
}

.account-modal.active {
  transform: translateX(0);
}

/* Header */
.account-header {
  padding: 1.5rem;
  background: linear-gradient(135deg, #007bff, #00c6ff);
  color: #fff;
  border-top-left-radius: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.account-close {
  background: none;
  border: none;
  font-size: 1.8rem;
  cursor: pointer;
  color: white;
  transition: transform 0.2s ease;
}

.account-close:hover {
  transform: scale(1.2);
}

/* Content */
.account-content {
  padding: 2rem 1.5rem;
  font-family: 'Segoe UI', sans-serif;
}

.account-section {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #eaeaea;
}

.account-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.account-section h3 {
  color: #007bff;
  margin-bottom: 1.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #cce5ff;
  font-size: 1.3rem;
}

/* Avatar */
.account-details {
  display: flex;
  gap: 1.5rem;
  align-items: center;
  margin-bottom: 2rem;
}

.account-avatar {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background-color: #e0f0ff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3rem;
  color: #007bff;
  box-shadow: 0 5px 15px rgba(0, 123, 255, 0.2);
  transition: transform 0.3s ease;
}

.account-avatar:hover {
  transform: rotate(10deg);
}

/* Info */
.account-info p {
  margin-bottom: 0.5rem;
  color: #333;
  font-size: 1.1rem;
}

.account-info strong {
  color: #0056b3;
  font-weight: 600;
}


 


/* Security Tips */
.security-tips {
  background: #f0f8ff;
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1.5rem;
}

.security-tips h4 {
  color: #007bff;
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
  gap: 8px;
}

.security-tips ul {
  list-style: none;
  padding-left: 1rem;
}

.security-tips li {
  margin-bottom: 0.5rem;
  position: relative;
  padding-left: 1.5rem;
}

.security-tips li:before {
  content: "✓";
  position: absolute;
  left: 0;
  color: #007bff;
  font-weight: bold;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .account-modal {
    width: 100%;
    border-radius: 0;
  }
  
  .account-details {
    flex-direction: column;
    text-align: center;
  }
}

    
  </style>
</head>
<body class="<?php echo $loggedIn ? 'user-logged-in' : ''; ?>">
  <header>
    <a href="index.php" class="logo">
      <img src="https://i.postimg.cc/qBX7DSGZ/computer-retail-and-repair-shop-logo-commercial-logo-design-vector-template-W7-H37-M.jpg" alt="DigiTech Logo" style="height: 40px;">
      <span class="logo-text">DigiTech</span>
    </a>
    
    <button class="mobile-menu-toggle" aria-label="Toggle navigation">
      <i class="fas fa-bars"></i>
    </button>
    
    <nav class="main-nav">
      <a href="#hero">Home</a>
      <a href="#products">Products</a>
      <a href="#categories">Categories</a>

      <?php if ($loggedIn): ?>
    <a href="#" id="accountBtn" onclick=toggleAccountModal()>
      <i class="fas fa-user"></i> Account
    </a>
    <a href="../logout.php" class="logout-link">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  <?php endif; ?>
      
      <a href="#" class="cart-link" onclick="showCart()" aria-label="Shopping Cart">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-count" id="cart-count">0</span>
      </a>
    </nav>
    
    <div class="search-container">
      <input class="search-bar" type="text" placeholder="Search products..." id="searchInput" aria-label="Search products">
      <button class="search-btn" aria-label="Search">
        <i class="fas fa-search"></i>
      </button>
    </div>
  </header>

  <main class="main">
    <section class="hero" id="hero">
      <div class="hero-content">
        <h1>Upgrade Your Tech Game</h1>
        <p>Premium electronics with exclusive discounts</p>
        <button class="cta-button" onclick="scrollToProducts()" aria-label="Shop Now">
          <i class="fas fa-arrow-right"></i> Shop Now
        </button>
      </div>
    </section>

   <!-- Updated Categories Section -->
<section class="categories-section" id="categories">
  <div class="section-header">
    <h2 class="section-title">Shop By Category</h2>
    <p>Discover our premium selection of tech products</p>
  </div>
  
  <div class="category-grid">
    <!-- Power Category -->
    <div class="category-card" onclick="filterCategory('Power')" role="button" tabindex="0" aria-label="Power products">
      <div class="category-bg" style="background-image: url('https://i.postimg.cc/3wZCvVnZ/Anker-737-Powerbank-01-jpg.webp')"></div>
      <div class="category-overlay">
        <i class="fas fa-bolt category-icon"></i>
        <h3 class="category-name">Power Solutions</h3>
        <div class="category-hover-content">
          <p>High-capacity power banks, chargers, and battery solutions</p>
          <span class="shop-now">Shop Now <i class="fas fa-arrow-right"></i></span>
        </div>
      </div>
    </div>
    
    <!-- Printer Category -->
    <div class="category-card" onclick="filterCategory('printer')" role="button" tabindex="0" aria-label="Printer products">
      <div class="category-bg" style="background-image: url('https://i.postimg.cc/prQnwn4J/600mm-UV-DTF-Website-JPG-01.png')"></div>
      <div class="category-overlay">
        <i class="fas fa-print category-icon"></i>
        <h3 class="category-name">Printers</h3>
        <div class="category-hover-content">
          <p>High-quality printers for home and office use</p>
          <span class="shop-now">Shop Now <i class="fas fa-arrow-right"></i></span>
        </div>
      </div>
    </div>
    
    <!-- Electronics Category -->
    <div class="category-card" onclick="filterCategory('Electronics')" role="button" tabindex="0" aria-label="Electronics products">
      <div class="category-bg" style="background-image: url('https://images.unsplash.com/photo-1550009158-9ebf69173e03?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1501&q=80')"></div>
      <div class="category-overlay">
        <i class="fas fa-tv category-icon"></i>
        <h3 class="category-name">Electronics</h3>
        <div class="category-hover-content">
          <p>Cutting-edge gadgets and electronic devices</p>
          <span class="shop-now">Shop Now <i class="fas fa-arrow-right"></i></span>
        </div>
      </div>
    </div>
    
    <!-- Computing Category -->
    <div class="category-card" onclick="filterCategory('Computing')" role="button" tabindex="0" aria-label="Computing products">
      <div class="category-bg" style="background-image: url('https://images.unsplash.com/photo-1517336714731-489689fd1ca8?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1526&q=80')"></div>
      <div class="category-overlay">
        <i class="fas fa-laptop category-icon"></i>
        <h3 class="category-name">Computing</h3>
        <div class="category-hover-content">
          <p>Powerful laptops, desktops, and accessories</p>
          <span class="shop-now">Shop Now <i class="fas fa-arrow-right"></i></span>
        </div>
      </div>
    </div>
    
    <!-- Phones Category -->
    <div class="category-card" onclick="filterCategory('Phones')" role="button" tabindex="0" aria-label="Phone products">
      <div class="category-bg" style="background-image: url('https://images.unsplash.com/photo-1555774698-0b77e0d5fac6')"></div>
      <div class="category-overlay">
        <i class="fas fa-mobile-alt category-icon"></i>
        <h3 class="category-name">Smartphones</h3>
        <div class="category-hover-content">
          <p>Latest smartphones from top brands</p>
          <span class="shop-now">Shop Now <i class="fas fa-arrow-right"></i></span>
        </div>
      </div>
    </div>
  </div>
</section>
    <section id="products" class="section">
      <div class="section-header">
        <h2>Featured Products</h2>
        <div class="sort-options">
          <label for="sortSelect">Sort by:</label>
          <select id="sortSelect" onchange="sortProducts()" aria-label="Sort products">
            <option value="default">Default</option>
            <option value="price-low">Price: Low to High</option>
            <option value="price-high">Price: High to Low</option>
            <option value="rating">Rating</option>
          </select>
        </div>
      </div>
      <div class="product-grid" id="productGrid">
        <!-- Products loaded via JavaScript -->
      </div>
      <div class="pagination" id="pagination">
        <!-- Pagination controls -->
      </div>
    </section>
  </main>
  
  <!-- Enhanced Account Modal -->
 <!-- Enhanced Account Modal -->
  <div class="account-modal" id="accountModal">
    <div class="account-header">
      <h2>My Account</h2>
      <button class="account-close" onclick="toggleAccountModal()">&times;</button>
    </div>
    
    <div class="account-content">
      <div class="account-section">
        <div class="account-details">
          <div class="account-avatar">
            <i class="fas fa-user"></i>
          </div>
          <div class="account-info">
            <p><strong>Name:</strong> <?php echo $_SESSION['user_name'] ?? 'Not available'; ?></p>
            <p><strong>Email:</strong> <?php echo $_SESSION['user_email'] ?? 'Not available'; ?></p>

             <p style="margin-top: 1rem;">
                         <a href="forgot-password-visual.php?email=<?php echo urlencode($_SESSION['user_email']); ?>" 
       style="font-size: 0.875rem; color: #333; text-decoration: none; display: inline-block;"
       onclick="event.stopPropagation()">
    <i class="fas fa-key"></i> Change Password</a>

                    </p>
          </div>
        </div>
      </div>
      
      <div class="account-section">
        <h3>Account Security</h3>
        <div class="security-tips">
          <h4><i class="fas fa-shield-alt"></i> Security Tips</h4>
          <ul>
            <li>Use a unique password for your account</li>
            <li>Enable two-factor authentication</li>
            <li>Regularly update your password</li>
            <li>Never share your password with anyone</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Cart Sidebar -->
  <div class="cart-sidebar" id="cartSidebar">
    <div class="cart-header">
      <h3>Your Cart</h3>
      <button class="close-cart" onclick="closeCart()" aria-label="Close cart">&times;</button>
    </div>
    <div class="cart-items" id="cartItems">
      <!-- Cart items will be loaded here -->
    </div>
    <div class="cart-summary">
      <div class="cart-total">
        <span>Total:</span>
        <span id="cartTotal">KSh 0</span>
      </div>
      <button class="checkout-btn" onclick="proceedToCheckout()">Proceed to Checkout</button>
    </div>
  </div>

  <!-- Toast Notification -->
  <div class="toast" id="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <i class="fas fa-check-circle"></i>
    <span id="toastMessage">Item added to cart</span>
  </div>

  <footer>
    <div class="footer-content">
      <div class="footer-section">
        <h4>Shop</h4>
        <ul>
          <li><a href="#products">All Products</a></li>
          <li><a href="#categories">Categories</a></li>
          
        </ul>
      </div>
     
      <div class="footer-section newsletter">
        <h4>Newsletter</h4>
        <form id="newsletterForm">
          <input type="email" placeholder="Your email" required aria-label="Email for newsletter">
          <button type="submit">Subscribe</button>
        </form>
        <div class="social-links">
          <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
          <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2025 DigiTech Marketplace. All Rights Reserved.</p>
      <div class="payment-methods">
        <i class="fab fa-cc-visa" aria-label="Visa"></i>
        <i class="fab fa-cc-mastercard" aria-label="Mastercard"></i>
        <i class="fab fa-cc-paypal" aria-label="PayPal"></i>
      </div>
    </div>
  </footer>

  <!-- Loading Spinner -->
  <div class="loading-spinner" id="loadingSpinner" aria-live="polite" aria-busy="true">
    <div class="spinner"></div>
  </div>
 
  <script>
    // Enhanced Product Data with more details
    const products = [
      {
        id: 1,
        name: "Wireless Earbuds Pro",
        price: 5999,
        original: 7999,
        rating: 4.5,
        reviews: 128,
        image: "https://images.unsplash.com/photo-1590658268037-6bf12165a8df?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80",
        category: "Electronics",
        stock: 15,
        description: "Premium wireless earbuds with active noise cancellation and 30-hour battery life. Features Bluetooth 5.0 and IPX4 water resistance.",
        specs: {
          color: "Black/White",
          connectivity: "Bluetooth 5.0",
          battery: "30 hours",
          warranty: "1 year"
        }
      },
      {
        id: 2,
        name: "Smartphone X",
        price: 35999,
        original: 42999,
        rating: 4.2,
        reviews: 256,
        image: "https://images.unsplash.com/photo-1601784551446-20c9e07cdbdb?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80",
        category: "Phones",
        stock: 8,
        description: "Flagship smartphone with 6.7\" AMOLED display, triple camera system, and 128GB storage. Powered by the latest Snapdragon processor.",
        specs: {
          storage: "128GB",
          ram: "8GB",
          display: "6.7\" AMOLED",
          camera: "Triple 48MP"
        }
      },
      {
        id: 3,
        name: "Gaming Laptop",
        price: 89999,
        original: 99999,
        rating: 4.8,
        reviews: 84,
        image: "https://images.unsplash.com/photo-1593642632823-8f785ba67e45?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80",
        category: "Computing",
        stock: 5,
        description: "High-performance gaming laptop with RTX 3060 graphics, 16GB RAM, 1TB SSD, and 144Hz display. Perfect for gamers and creators.",
        specs: {
          processor: "Intel i7-11800H",
          gpu: "RTX 3060",
          ram: "16GB",
          storage: "1TB SSD"
        }
      },
      {
        id: 4,
        name: "Smart Watch",
        price: 12999,
        original: 15999,
        rating: 4.1,
        reviews: 192,
        image: "https://images.unsplash.com/photo-1523275335684-37898b6baf30?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80",
        category: "Electronics",
        stock: 12,
        description: "Fitness tracker with heart rate monitoring, sleep tracking, and 7-day battery life. Compatible with iOS and Android.",
        specs: {
          battery: "7 days",
          compatibility: "iOS & Android",
          features: "Heart rate, Sleep tracking",
          waterResistance: "5ATM"
        }
      },
      {
        id: 5,
        name: "Bluetooth Speaker",
        price: 7999,
        original: 9999,
        rating: 4.3,
        reviews: 76,
        image: "https://images.unsplash.com/photo-1572569511254-d8f925fe2cbb?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80",
        category: "Electronics",
        stock: 20,
        description: "Portable speaker with 20W output, deep bass, and waterproof design. Perfect for outdoor adventures and parties.",
        specs: {
          power: "20W",
          battery: "12 hours",
          waterproof: "IPX7",
          connectivity: "Bluetooth 5.0"
        }
      },
      {
        id: 6,
        name: "Mechanical Keyboard",
        price: 6999,
        original: 8999,
        rating: 4.7,
        reviews: 143,
        image: "https://images.unsplash.com/photo-1587829741301-dc798b83add3?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80",
        category: "Electronics",
        stock: 10,
        description: "RGB mechanical keyboard with customizable switches and per-key lighting. Ideal for gamers and programmers.",
        specs: {
          switches: "Customizable",
          backlight: "RGB",
          connectivity: "USB/Wireless",
          layout: "Full-size"
        }
      },
      {
        id: 7,
        name: "Wireless Mouse",
        price: 2999,
        original: 3999,
        rating: 4.0,
        reviews: 98,
        image: "https://images.unsplash.com/photo-1527814050087-3793815479db?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80",
        category: "Electronics",
        stock: 25,
        description: "Ergonomic wireless mouse with 12-month battery life and precision tracking. Comfortable for all-day use.",
        specs: {
          dpi: "16000",
          battery: "12 months",
          connectivity: "Bluetooth/2.4GHz",
          buttons: "6 programmable"
        }
      },
      {
        id: 8,
        name: "4K Monitor",
        price: 24999,
        original: 29999,
        rating: 4.6,
        reviews: 67,
        image: "https://i.postimg.cc/g2sB2ggM/27inchmonitor-2048px-DSF4695.webp",
        category: "Computing",
        stock: 7,
        description: "27-inch 4K monitor with HDR, 99% sRGB coverage, and 60Hz refresh rate. Perfect for designers and content creators.",
        specs: {
          resolution: "3840x2160",
          refreshRate: "60Hz",
          panel: "IPS",
          ports: "HDMI, DisplayPort, USB-C"
        }
      },
      {
        id: 9,
        name: "Epson Printer",
        price: 44999,
        original: 49999,
        rating: 4.4,
        reviews: 60,
        image: "https://i.postimg.cc/C55Q3kGv/epson.jpg",
        category: "printer",
        stock: 5,
        description: "The Epson printer offers high-quality printing, delivering crisp documents and vibrant photos ideal for both home and office use. With EcoTank technology, it features refillable ink tanks that help save on printing costs.",
        specs: {
          type: "Inkjet",
          technology: "EcoTank",
          printSpeed: "15ppm (black)",
          connectivity: "Wi-Fi, USB"
        }
      },
      {
        id: 10,
        name: "iPhone 13 Pro Max",
        price: 84999,
        original: 89000,
        rating: 4.9,
        reviews: 80,
        image: "https://i.postimg.cc/xCsQG4ZF/61c-Ti9-MBq-OL-AC-SL1500.jpg",
        category: "Phones",
        stock: 15,
        description: "The iPhone 13 Pro Max features a stunning 6.7-inch Super Retina XDR display for vibrant visuals and sharp detail. Powered by the A15 Bionic chip, it delivers exceptional performance and power efficiency.",
        specs: {
          storage: "128GB/256GB/512GB",
          display: "6.7\" Super Retina XDR",
          camera: "Triple 12MP",
          battery: "Up to 28 hours"
        }
      },
      {
        id: 11,
        name: "Power Bank 20000mAh",
        price: 3999,
        original: 4999,
        rating: 4.2,
        reviews: 45,
        image: "https://i.postimg.cc/3wQT5Sbd/powerbanks.png",
        category: "Power",
        stock: 12,
        description: "High-capacity 20000mAh power bank with fast charging support for multiple devices simultaneously. Features USB-C PD and Qi wireless charging.",
        specs: {
          capacity: "20000mAh",
          output: "18W PD",
          ports: "2x USB-A, 1x USB-C",
          wireless: "Qi compatible"
        }
      }
    ];

    // Cart functionality
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    let currentPage = 1;
    const productsPerPage = 8;

    // Initialize the page
    document.addEventListener('DOMContentLoaded', () => {
      updateCartCount();
      loadProducts();
      setupEventListeners();
      setupMobileMenu();
      
      <?php if ($loggedIn): ?>
        // Add event listener for account button
        const accountBtn = document.getElementById('accountBtn');
        if (accountBtn) {
          accountBtn.addEventListener('click', (e) => {
            e.preventDefault();
            toggleAccountModal();
          });
        }
      <?php endif; ?>
    });

    function setupEventListeners() {
      // Search functionality
      document.getElementById('searchInput').addEventListener('input', debounce(searchProducts, 300));
      document.querySelector('.search-btn').addEventListener('click', () => {
        searchProducts();
      });

      // Sort functionality
      document.getElementById('sortSelect').addEventListener('change', sortProducts);

      // Keyboard navigation for category cards
      document.querySelectorAll('.category-card').forEach(card => {
        card.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            const category = card.querySelector('.category-name').textContent;
            filterCategory(category);
          }
        });
      });
      
      // Password form submission
      document.getElementById('changePasswordForm').addEventListener('submit', handlePasswordChange);
    }

    function setupMobileMenu() {
      const toggle = document.querySelector('.mobile-menu-toggle');
      const nav = document.querySelector('.main-nav');
      
      toggle.addEventListener('click', () => {
        nav.classList.toggle('active');
        toggle.innerHTML = nav.classList.contains('active') ? 
          '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
      });
    }

    function toggleAccountModal() {
      const modal = document.getElementById('accountModal');
      modal.classList.toggle('active');
      document.body.classList.toggle('modal-open');
        // Focus management for accessibility
    if (modal.classList.contains('active')) {
      setTimeout(() => {
        const closeBtn = modal.querySelector('.account-close');
        if (closeBtn) closeBtn.focus();
      }, 100);
    }
  
    }

    function togglePasswordVisibility(fieldId) {
      const passwordField = document.getElementById(fieldId);
      const toggleIcon = passwordField.nextElementSibling.querySelector('i');
      
      if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
      } else {
        passwordField.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
      }
    }
    
    function checkPasswordStrength(password) {
      const strengthBar = document.getElementById('passwordStrength');
      const strengthLabel = document.getElementById('strengthLabel');
      
      // Reset classes
      strengthBar.className = 'password-strength';
      strengthLabel.className = 'strength-label';
      
      if (password.length === 0) {
        return;
      }
      
      let strength = 0;
      if (password.length >= 8) strength += 1;
      if (/[A-Z]/.test(password)) strength += 1;
      if (/[0-9]/.test(password)) strength += 1;
      if (/[^A-Za-z0-9]/.test(password)) strength += 1;
      
      if (strength < 2) {
        strengthBar.classList.add('weak');
        strengthLabel.classList.add('weak');
        strengthLabel.textContent = 'Weak';
      } else if (strength < 4) {
        strengthBar.classList.add('medium');
        strengthLabel.classList.add('medium');
        strengthLabel.textContent = 'Medium';
      } else {
        strengthBar.classList.add('strong');
        strengthLabel.classList.add('strong');
        strengthLabel.textContent = 'Strong';
      }
    }
    
    function handlePasswordChange(e) {
      e.preventDefault();
      
      const currentPassword = document.getElementById('currentPassword').value;
      const newPassword = document.getElementById('newPassword').value;
      const confirmPassword = document.getElementById('confirmPassword').value;
      const messageDiv = document.getElementById('passwordChangeMessage');
      
      // Basic validation
      if (newPassword !== confirmPassword) {
        messageDiv.textContent = 'Passwords do not match!';
        messageDiv.className = 'message error';
        messageDiv.style.display = 'block';
        return;
      }
      
      if (newPassword.length < 8) {
        messageDiv.textContent = 'Password must be at least 8 characters long!';
        messageDiv.className = 'message error';
        messageDiv.style.display = 'block';
        return;
      }
      
      // Simulate password change (in a real app, you would send this to your server)
      messageDiv.textContent = 'Password updated successfully!';
      messageDiv.className = 'message success';
      messageDiv.style.display = 'block';
      
      // Reset form
      setTimeout(() => {
        e.target.reset();
        messageDiv.style.display = 'none';
        document.getElementById('passwordStrength').className = 'password-strength weak';
        document.getElementById('strengthLabel').textContent = 'Weak';
        document.getElementById('strengthLabel').className = 'strength-label weak';
      }, 3000);
    }

    function loadProducts(productsToLoad = products) {
      showLoading();
      
      // Calculate pagination
      const startIndex = (currentPage - 1) * productsPerPage;
      const endIndex = startIndex + productsPerPage;
      const paginatedProducts = productsToLoad.slice(startIndex, endIndex);
      
      // Render products
      renderProducts(paginatedProducts);
      
      // Render pagination
      renderPagination(productsToLoad.length);
      
      hideLoading();
    }

    function renderProducts(productsToRender) {
      const productGrid = document.getElementById('productGrid');
      productGrid.innerHTML = '';
      
      if (productsToRender.length === 0) {
        productGrid.innerHTML = '<div class="no-results">No products found matching your search</div>';
        return;
      }
      
      productsToRender.forEach(product => {
        const discount = Math.round(((product.original - product.price) / product.original) * 100);
        const stars = renderStars(product.rating);
        const isLowStock = product.stock <= 5;
        
        productGrid.innerHTML += `
          <div class="product-card" data-id="${product.id}" tabindex="0" 
               aria-label="${product.name}, Price: KSh ${product.price.toLocaleString()}, Rating: ${product.rating} stars">
            ${discount > 0 ? `<div class="product-badge">${discount}% OFF</div>` : ''}
            <img src="${product.image}" alt="${product.name}" loading="lazy">
            <h3>${product.name}</h3>
            <div class="price">KSh ${product.price.toLocaleString()} 
              ${product.original > product.price ? 
                `<span class="original-price">KSh ${product.original.toLocaleString()}</span>` : ''}
            </div>
            <div class="rating">
              ${stars}
              <span class="review-count">(${product.reviews})</span>
            </div>
            <div class="stock-status">
              ${product.stock > 5 ? 
                '<i class="fas fa-check-circle in-stock"></i> In Stock' : 
                `<i class="fas fa-exclamation-circle low-stock"></i> Only ${product.stock} left`}
            </div>
            <button onclick="addToCart(${product.id})" aria-label="Add ${product.name} to cart">
              <i class="fas fa-cart-plus"></i> Add to Cart
            </button>
            <button class="quick-view" onclick="showQuickView(${product.id})" aria-label="Quick view of ${product.name}">
              <i class="fas fa-eye"></i> Quick View
            </button>
          </div>
        `;
      });

      // Add keyboard navigation for product cards
      document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') {
            const productId = parseInt(card.getAttribute('data-id'));
            showQuickView(productId);
          }
        });
      });
    }

    function renderStars(rating) {
      let stars = '';
      const fullStars = Math.floor(rating);
      const hasHalfStar = rating % 1 >= 0.5;
      
      for (let i = 1; i <= 5; i++) {
        if (i <= fullStars) {
          stars += '<i class="fas fa-star"></i>';
        } else if (i === fullStars + 1 && hasHalfStar) {
          stars += '<i class="fas fa-star-half-alt"></i>';
        } else {
          stars += '<i class="far fa-star"></i>';
        }
      }
      return stars;
    }

    function renderPagination(totalProducts) {
      const totalPages = Math.ceil(totalProducts / productsPerPage);
      const pagination = document.getElementById('pagination');
      
      if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
      }
      
      let paginationHTML = '<div class="pagination-controls">';
      
      // Previous button
      paginationHTML += `
        <button class="pagination-btn ${currentPage === 1 ? 'disabled' : ''}" 
                onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}
                aria-label="Previous page">
          <i class="fas fa-chevron-left"></i>
        </button>
      `;
      
      // Page numbers
      const maxVisiblePages = 5;
      let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
      let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
      
      // Adjust if we're at the end
      if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
      }
      
      // First page and ellipsis
      if (startPage > 1) {
        paginationHTML += `
          <button class="pagination-btn" onclick="changePage(1)" aria-label="Page 1">
            1
          </button>
          ${startPage > 2 ? '<span class="pagination-ellipsis">...</span>' : ''}
        `;
      }
      
      // Visible pages
      for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
          <button class="pagination-btn ${i === currentPage ? 'active' : ''}" 
                  onclick="changePage(${i})" aria-label="Page ${i}">
            ${i}
          </button>
        `;
      }
      
      // Last page and ellipsis
      if (endPage < totalPages) {
        paginationHTML += `
          ${endPage < totalPages - 1 ? '<span class="pagination-ellipsis">...</span>' : ''}
          <button class="pagination-btn" onclick="changePage(${totalPages})" aria-label="Page ${totalPages}">
            ${totalPages}
          </button>
        `;
      }
      
      // Next button
      paginationHTML += `
        <button class="pagination-btn ${currentPage === totalPages ? 'disabled' : ''}" 
                onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}
                aria-label="Next page">
          <i class="fas fa-chevron-right"></i>
        </button>
      `;
      
      paginationHTML += '</div>';
      pagination.innerHTML = paginationHTML;
    }

    function changePage(page) {
      if (page < 1 || page > Math.ceil(products.length / productsPerPage)) return;
      
      currentPage = page;
      loadProducts();
      window.scrollTo({
        top: document.getElementById('products').offsetTop - 100,
        behavior: 'smooth'
      });
      
      // Focus on products section for screen readers
      document.getElementById('products').setAttribute('tabindex', '-1');
      document.getElementById('products').focus();
    }

    function filterCategory(category) {
      const filtered = category === 'all' ? products : products.filter(p => p.category.toLowerCase() === category.toLowerCase());
      currentPage = 1;
      loadProducts(filtered);
      
      // Update the sort select to default
      document.getElementById('sortSelect').value = 'default';
      
      // Scroll to products section
      document.getElementById('products').scrollIntoView({
        behavior: 'smooth'
      });
      
      // Announce filter change for screen readers
      const liveRegion = document.createElement('div');
      liveRegion.setAttribute('aria-live', 'polite');
      liveRegion.style.position = 'absolute';
      liveRegion.style.left = '-9999px';
      liveRegion.textContent = `Showing ${category === 'all' ? 'all' : category} products`;
      document.body.appendChild(liveRegion);
      setTimeout(() => document.body.removeChild(liveRegion), 1000);
    }

    function searchProducts() {
      const searchTerm = document.getElementById('searchInput').value.trim().toLowerCase();
      
      if (!searchTerm) {
        // If search is empty, show all products
        loadProducts(products);
        return;
      }
      
      // Search in name, category, and description
      const results = products.filter(p => {
        return (
          p.name.toLowerCase().includes(searchTerm) || 
          p.category.toLowerCase().includes(searchTerm) ||
          (p.description && p.description.toLowerCase().includes(searchTerm))
        );
      });
      
      currentPage = 1;
      loadProducts(results);
      
      // Show message if no results found
      if (results.length === 0) {
        showToast('No products found matching your search', 'info');
      }
    }

    function sortProducts() {
      const sortValue = document.getElementById('sortSelect').value;
      let sortedProducts = [...products];
      
      switch (sortValue) {
        case 'price-low':
          sortedProducts.sort((a, b) => a.price - b.price);
          break;
        case 'price-high':
          sortedProducts.sort((a, b) => b.price - a.price);
          break;
        case 'rating':
          sortedProducts.sort((a, b) => b.rating - a.rating);
          break;
        default:
          // Default sorting (by ID)
          sortedProducts.sort((a, b) => a.id - b.id);
          break;
      }
      
      currentPage = 1;
      loadProducts(sortedProducts);
    }

    function addToCart(productId) {
      const product = products.find(p => p.id === productId);
      if (!product) return;
      
      const existingItem = cart.find(item => item.id === productId);
      
      if (existingItem) {
        if (existingItem.quantity >= product.stock) {
          showToast(`Only ${product.stock} available in stock`, 'warning');
          return;
        }
        existingItem.quantity++;
      } else {
        cart.push({
          id: product.id,
          name: product.name,
          price: product.price,
          image: product.image,
          quantity: 1,
          maxQuantity: product.stock
        });
      }
      
      updateCart();
      showToast(`${product.name} added to cart`);
      
      // Focus on cart button after adding
      document.querySelector('.cart-link').focus();
    }

    function updateCart() {
      localStorage.setItem('cart', JSON.stringify(cart));
      updateCartCount();
      updateCartSidebar();
    }

    function updateCartCount() {
      const count = cart.reduce((total, item) => total + item.quantity, 0);
      document.getElementById('cart-count').textContent = count;
      
      // Update aria-label for screen readers
      const cartLink = document.querySelector('.cart-link');
      cartLink.setAttribute('aria-label', `Shopping Cart, ${count} items`);
    }

    function updateCartSidebar() {
      const cartItemsEl = document.getElementById('cartItems');
      const cartTotalEl = document.getElementById('cartTotal');
      
      if (cart.length === 0) {
        cartItemsEl.innerHTML = '<div class="empty-cart">Your cart is empty</div>';
        cartTotalEl.textContent = 'KSh 0';
        return;
      }
      
      let itemsHTML = '';
      let total = 0;
      
      cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        
        itemsHTML += `
          <div class="cart-item" data-id="${item.id}">
            <img src="${item.image}" alt="${item.name}" loading="lazy">
            <div class="cart-item-details">
              <h4>${item.name}</h4>
              <div class="cart-item-price">KSh ${item.price.toLocaleString()} x ${item.quantity}</div>
              <div class="cart-item-total">KSh ${itemTotal.toLocaleString()}</div>
            </div>
            <div class="cart-item-actions">
              <button onclick="adjustCartItem(${item.id}, -1)" aria-label="Decrease quantity">
                <i class="fas fa-minus"></i>
              </button>
              <span>${item.quantity}</span>
              <button onclick="adjustCartItem(${item.id}, 1)" aria-label="Increase quantity" 
                      ${item.quantity >= item.maxQuantity ? 'disabled' : ''}>
                <i class="fas fa-plus"></i>
              </button>
              <button onclick="removeCartItem(${item.id})" aria-label="Remove item">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        `;
      });
      
      cartItemsEl.innerHTML = itemsHTML;
      cartTotalEl.textContent = `KSh ${total.toLocaleString()}`;
    }

    function adjustCartItem(productId, change) {
      const item = cart.find(item => item.id === productId);
      if (!item) return;
      
      const product = products.find(p => p.id === productId);
      if (!product) return;
      
      if (change > 0 && item.quantity >= product.stock) {
        showToast(`Only ${product.stock} available in stock`, 'warning');
        return;
      }
      
      item.quantity += change;
      
      if (item.quantity <= 0) {
        cart = cart.filter(i => i.id !== productId);
      }
      
      updateCart();
      showToast(`Cart updated`);
    }

    function showCart() {
  updateCartSidebar();
  document.getElementById('cartSidebar').classList.add('active');
  document.body.classList.add('cart-open');
  
  // Focus on close button when cart opens
  setTimeout(() => {
    document.querySelector('.close-cart').focus();
  }, 100);
}

function closeCart() {
  document.getElementById('cartSidebar').classList.remove('active');
  document.body.classList.remove('cart-open');
  
  // Focus back on cart button
  document.querySelector('.cart-link').focus();
}

// Ensure 'cart' is defined globally (e.g., loaded from localStorage on initial page load)
// For example, at the top of your main JS file:
// let cart = JSON.parse(localStorage.getItem('cart')) || [];

// Ensure 'cart' is defined globally (e.g., loaded from localStorage on initial page load)
// For example, at the top of your main JS file:
// let cart = JSON.parse(localStorage.getItem('cart')) || [];

function proceedToCheckout() {
    // Check if the cart is empty (client-side check before sending)
    if (cart.length === 0) {
        // Assuming showToast is defined elsewhere in your code
        showToast('Your cart is empty', 'warning');
        return;
    }

    try {
        // Create a dynamic form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/Digitech/user/checkout.php'; // Target checkout.php

        // Create a hidden input field to hold the stringified cart data
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'cart_data'; // Name this input 'cart_data'
        hiddenInput.value = JSON.stringify(cart); // Stringify the cart array

        // Append the input to the form
        form.appendChild(hiddenInput);

        // Append the form to the document body (or any accessible element)
        document.body.appendChild(form);

        // Submit the form
        form.submit();

    } catch (error) {
        console.error('Error in proceedToCheckout function:', error);
        alert('An error occurred while preparing for checkout. Please try again.');
    }
}
  

async function saveCartToSession() {
  showLoading();
  try {
    const response = await fetch('../user/save-cart.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ cart })
    });
    
    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Failed to save cart');
    }
  } catch (error) {
    console.error('Error saving cart:', error);
    throw error; // Rethrow for caller handling
  } finally {
    hideLoading();
  }
  function openModal() {
  // Open login modal
}
}

    function showQuickView(productId) {
      const product = products.find(p => p.id === productId);
      if (!product) return;
      
      // Create modal HTML
      const modalHTML = `
        <div class="modal-overlay active" id="quickViewModal">
          <div class="modal-container" style="max-width: 800px;">
            <div class="modal-header">
              <h2 class="modal-title">${product.name}</h2>
              <button class="close-btn" onclick="closeQuickView()" aria-label="Close quick view">&times;</button>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
              <div>
                <img src="${product.image}" alt="${product.name}" style="width: 100%; border-radius: var(--border-radius);">
              </div>
              <div>
                <div class="price" style="font-size: 1.5rem; margin-bottom: 1rem;">
                  KSh ${product.price.toLocaleString()}
                  ${product.original > product.price ? 
                    `<span class="original-price">KSh ${product.original.toLocaleString()}</span>` : ''}
                </div>
                
                <div class="rating" style="margin-bottom: 1rem;">
                  ${renderStars(product.rating)}
                  <span class="review-count">(${product.reviews} reviews)</span>
                </div>
                
                <div class="stock-status" style="margin-bottom: 1.5rem;">
                  ${product.stock > 5 ? 
                    '<i class="fas fa-check-circle in-stock"></i> In Stock' : 
                    `<i class="fas fa-exclamation-circle low-stock"></i> Only ${product.stock} left`}
                </div>
                
                <p style="margin-bottom: 1.5rem;">${product.description}</p>
                
                <div style="margin-bottom: 2rem;">
                  <h4 style="margin-bottom: 0.5rem;">Specifications</h4>
                  <ul style="list-style: none;">
                    ${Object.entries(product.specs || {}).map(([key, value]) => 
                      `<li><strong>${key}:</strong> ${value}</li>`).join('')}
                  </ul>
                </div>
                
                <button onclick="addToCart(${product.id}); closeQuickView();" class="btn" style="width: auto; padding: 0.8rem 2rem;">
                  <i class="fas fa-cart-plus"></i> Add to Cart
                </button>
              </div>
            </div>
          </div>
        </div>
      `;
      
      // Add to DOM
      document.body.insertAdjacentHTML('beforeend', modalHTML);
      document.body.classList.add('modal-open');
      
      // Focus on close button
      setTimeout(() => {
        document.querySelector('#quickViewModal .close-btn').focus();
      }, 100);
    }

    function closeQuickView() {
      const modal = document.getElementById('quickViewModal');
      if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
          modal.remove();
          document.body.classList.remove('modal-open');
        }, 300);
      }
    }

    function showToast(message, type = 'success', duration = 3000) {
      const toast = document.getElementById('toast');
      const toastMessage = document.getElementById('toastMessage');
      
      // Clear any existing timeout
      if (toast.timeoutId) {
        clearTimeout(toast.timeoutId);
      }
      
      // Update toast content
      toast.className = 'toast';
      toast.classList.add(type);
      toastMessage.innerHTML = message;
      
      // Set appropriate icon
      const icon = toast.querySelector('i');
      switch(type) {
        case 'success':
          icon.className = 'fas fa-check-circle';
          break;
        case 'error':
          icon.className = 'fas fa-exclamation-circle';
          break;
        case 'warning':
          icon.className = 'fas fa-exclamation-triangle';
          break;
        case 'info':
          icon.className = 'fas fa-info-circle';
          break;
      }
      
      // Show toast
      toast.classList.add('show');
      
      // Hide after duration
      toast.timeoutId = setTimeout(() => {
        toast.classList.remove('show');
      }, duration);
    }

    function scrollToProducts() {
      document.getElementById('products').scrollIntoView({
        behavior: 'smooth'
      });
      
      // Focus on products section for screen readers
      setTimeout(() => {
        document.getElementById('products').setAttribute('tabindex', '-1');
        document.getElementById('products').focus();
      }, 500);
    }

    function showLoading() {
      document.getElementById('loadingSpinner').classList.add('active');
    }

    function hideLoading() {
      document.getElementById('loadingSpinner').classList.remove('active');
    }

    // Utility function to debounce rapid events
    function debounce(func, wait) {
      let timeout;
      return function() {
        const context = this, args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
      };
    }
  </script>
</body>
</html>