<?php
// forgot-password.php
session_start();
$status = $_SESSION['reset_status'] ?? null;
$error = $_SESSION['reset_error'] ?? null;
$email = $_SESSION['reset_email'] ?? '';

// Clear session messages
unset($_SESSION['reset_status']);
unset($_SESSION['reset_error']);
unset($_SESSION['reset_email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset | Digitech Marketplace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --success: #4cc9f0;
            --error: #e63946;
            --border: #dee2e6;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            display: flex;
            max-width: 1000px;
            width: 100%;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        
        .left-panel {
            flex: 1;
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .left-panel::before {
            content: "";
            position: absolute;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><polygon fill="rgba(255,255,255,0.05)" points="0,100 100,0 100,100"/></svg>');
            background-size: 50%;
            top: -50%;
            left: -50%;
            transform: rotate(45deg);
            z-index: 0;
        }
        
        .left-panel-content {
            position: relative;
            z-index: 1;
            max-width: 400px;
        }
        
        .left-panel h2 {
            font-size: 32px;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .left-panel p {
            font-size: 18px;
            line-height: 1.6;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        
        .features {
            text-align: left;
            width: 100%;
            margin-top: 30px;
        }
        
        .feature {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .feature i {
            margin-right: 15px;
            font-size: 20px;
            color: var(--success);
        }
        
        .right-panel {
            flex: 1;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo h1 {
            color: var(--primary);
            font-size: 32px;
            font-weight: 800;
        }
        
        .logo span {
            color: var(--secondary);
        }
        
        .form-container {
            max-width: 400px;
            margin: 0 auto;
            width: 100%;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-header h2 {
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .form-header p {
            color: var(--gray);
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 15px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 15px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        .form-group input.error {
            border-color: var(--error);
        }
        
        .error-message {
            color: var(--error);
            font-size: 14px;
            margin-top: 6px;
            display: none;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 25px;
            color: var(--gray);
        }
        
        .back-to-login a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-to-login a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .alert-success {
            background: rgba(76, 201, 240, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }
        
        .alert-error {
            background: rgba(230, 57, 70, 0.1);
            border: 1px solid var(--error);
            color: var(--error);
        }
        
        .alert i {
            margin-right: 10px;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .left-panel {
                padding: 40px 20px;
            }
            
            .right-panel {
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="left-panel-content">
                <h2>Reset Your Password</h2>
                <p>Enter your email address and we'll send you a link to reset your password.</p>
                
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-shield-alt"></i>
                        <div>Secure password reset with encrypted tokens</div>
                    </div>
                    <div class="feature">
                        <i class="fas fa-clock"></i>
                        <div>Links expire automatically after 30 minutes</div>
                    </div>
                    <div class="feature">
                        <i class="fas fa-lock"></i>
                        <div>Industry-standard security practices</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="right-panel">
            <div class="logo">
                <h1>Digi<span>tech</span></h1>
                <p>Marketplace Solution</p>
            </div>
            
            <div class="form-container">
                <div class="form-header">
                    <h2>Forgot Password?</h2>
                    <p>Enter your email to reset your password</p>
                </div>
                
                <?php if ($status === 'sent'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <strong>Reset Link Sent!</strong>
                        <p>We've sent a password reset link to <strong><?= htmlspecialchars($email) ?></strong>.</p>
                        <p>Please check your inbox and follow the instructions.</p>
                    </div>
                <?php elseif ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Error!</strong> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form id="resetForm" action="send-password-reset.php" method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email address" 
                               value="<?= htmlspecialchars($email) ?>" required>
                        <div class="error-message" id="email-error">Please enter a valid email address</div>
                    </div>
                    
                    <button type="submit" class="btn">Send Reset Link</button>
                </form>
                
                <div class="back-to-login">
                    Remember your password? <a href='../index.php'>Log in</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('resetForm');
            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('email-error');
            
            // Validate email format
            function validateEmail(email) {
                const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return re.test(String(email).toLowerCase());
            }
            
            form.addEventListener('submit', function(e) {
                // Reset errors
                emailInput.classList.remove('error');
                emailError.style.display = 'none';
                
                // Validate email
                if (!emailInput.value.trim()) {
                    e.preventDefault();
                    emailInput.classList.add('error');
                    emailError.style.display = 'block';
                    emailError.textContent = 'Email is required';
                } else if (!validateEmail(emailInput.value.trim())) {
                    e.preventDefault();
                    emailInput.classList.add('error');
                    emailError.style.display = 'block';
                    emailError.textContent = 'Please enter a valid email address';
                }
            });
        });
    </script>
</body>
</html>