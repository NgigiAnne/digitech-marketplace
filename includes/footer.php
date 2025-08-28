    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h4>Shop</h4>
                <ul>
                    <li><a href="../user/dashboard.php">All Products</a></li>
                    <li><a href="../user/dashboard.php">Categories</a></li>
                    
                </ul>
            </div>
           
            
            <div class="footer-section newsletter">
                <h4>Newsletter</h4>
                <form id="newsletterForm" action="../subscribe.php" method="POST">
                    <input type="email" name="email" placeholder="Your email" required>
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
            <p>&copy; <?php echo date('Y'); ?> DigiTech Marketplace. All Rights Reserved.</p>
            <div class="payment-methods">
                <i class="fab fa-cc-visa" aria-label="Visa"></i>
                <i class="fab fa-cc-mastercard" aria-label="Mastercard"></i>
                <i class="fab fa-cc-paypal" aria-label="PayPal"></i>
            </div>
        </div>
    </footer>

    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner"></div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage">Item added to cart</span>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>