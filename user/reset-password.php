<?php
// Start session at the very top
session_start();

// Include the database configuration file
require_once __DIR__ . '/../includes/database.php';

$token = $_GET["token"] ?? null;

if (!$token) {
    die("Invalid token");
}

// ... [rest of your PHP token validation code remains the same] ...

$token_hash = hash("sha256", $token);

// Get database connection using your getDB() function
$mysqli = getDB();

// Verify we have a valid connection object
if (!($mysqli instanceof mysqli)) {
    die("Database connection error");
}

// Using 'users' table as per previous corrections
$sql = "SELECT * FROM users
        WHERE reset_token_hash = ?";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    die("Database error: " . $mysqli->error);
}

$stmt->bind_param("s", $token_hash);

if (!$stmt->execute()) {
    die("Database error: " . $stmt->error);
}

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user === null) {
    die("Token not found");
}

if (strtotime($user["reset_token_expires_at"]) <= time()) {
    die("Token has expired");
}

// Clear token after verification to prevent reuse
/*
$clearSql = "UPDATE users SET reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?";
$clearStmt = $mysqli->prepare($clearSql);
$clearStmt->bind_param("i", $user['id']);
$clearStmt->execute();
*/
?>
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ... [your existing head content] ... -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | DigiTech Marketplace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Add this to your existing styles */
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
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            padding: 40px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: var(--primary);
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 10px;
        }
        
        .logo span {
            color: var(--secondary);
        }
        
        .logo p {
            color: var(--gray);
            font-size: 16px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .form-header h2 {
            font-size: 26px;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .form-header p {
            color: var(--gray);
            font-size: 16px;
        }
        
        .security-status {
            background: rgba(76, 201, 240, 0.1);
            border: 1px solid var(--success);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }
        
        .security-status i {
            font-size: 24px;
            color: var(--success);
            margin-right: 15px;
        }
        
        .security-status p {
            font-size: 14px;
            color: var(--dark);
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
        
        .password-container {
            position: relative;
        }
        
        .password-container input {
            width: 100%;
            padding: 14px 45px 14px 15px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .password-container input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray);
        }
        
        .password-strength {
            height: 5px;
            background: #e9ecef;
            border-radius: 3px;
            margin-top: 10px;
            overflow: hidden;
            position: relative;
        }
        
        .password-strength::before {
            content: '';
            position: absolute;
            height: 100%;
            width: 0;
            background: var(--error);
            transition: width 0.3s, background 0.3s;
        }
        
        .password-strength[data-strength="weak"]::before {
            width: 30%;
            background: var(--error);
        }
        
        .password-strength[data-strength="medium"]::before {
            width: 60%;
            background: orange;
        }
        
        .password-strength[data-strength="strong"]::before {
            width: 100%;
            background: var(--success);
        }
        
        .password-criteria {
            margin-top: 5px;
            font-size: 13px;
            color: var(--gray);
        }
        
        .password-criteria ul {
            list-style: none;
            padding-left: 0;
            margin-top: 5px;
        }
        
        .password-criteria li {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .password-criteria li i {
            margin-right: 8px;
            font-size: 12px;
        }
        
        .password-criteria li.valid {
            color: var(--success);
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
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn i {
            margin-right: 10px;
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
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .card {
                padding: 20px;
            }
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            display: flex;
            align-items: center;
        }
        
        .error-message i {
            margin-right: 10px;
            font-size: 20px;
        }
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>Digi<span>tech</span></h1>
            <p>Marketplace Solution</p>
        </div>
        
        <div class="card">
            <!-- Display PHP errors -->
            <?php if (isset($_SESSION['reset_error'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($_SESSION['reset_error']) ?>
                </div>
                <?php unset($_SESSION['reset_error']); ?>
            <?php endif; ?>
            
            <div class="form-header">
                <h2>Reset Your Password</h2>
                <p>Create a new secure password for your account</p>
            </div>
            
            <div class="security-status">
                <i class="fas fa-shield-alt"></i>
                <p>This is a secure password reset page. Your token has been verified and will expire after this reset.</p>
            </div>
            
            <!-- FORM CHANGES START HERE -->
            <form id="resetForm" method="post" action="process-reset-password.php">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" required
                               placeholder="Enter your new password">
                        <span class="toggle-password" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <div class="password-strength" id="passwordStrength"></div>
                    <div class="password-criteria">
                        <p>Your password must contain:</p>
                        <ul>
                            <li id="lengthCriteria"><i class="fas fa-circle"></i> At least 8 characters</li>
                            <li id="letterCriteria"><i class="fas fa-circle"></i> At least one letter</li>
                            <li id="numberCriteria"><i class="fas fa-circle"></i> At least one number</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password_confirmation">Confirm New Password</label>
                    <div class="password-container">
                        <input type="password" id="password_confirmation" 
                               name="password_confirmation" required
                               placeholder="Repeat your new password">
                        <span class="toggle-password" id="togglePasswordConfirmation">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <div id="passwordMatch" class="error-message hidden">
                        <i class="fas fa-exclamation-circle"></i> Passwords do not match
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-lock"></i> Reset Password
                </button>
            </form>
            
            <div class="back-to-login">
                <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to login</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const passwordConfirm = document.getElementById('password_confirmation');
            const passwordStrength = document.getElementById('passwordStrength');
            const togglePassword = document.getElementById('togglePassword');
            const togglePasswordConfirmation = document.getElementById('togglePasswordConfirmation');
            const passwordMatch = document.getElementById('passwordMatch');
            const form = document.getElementById('resetForm');
            
            // Password visibility toggle
            function setupPasswordToggle(button, input) {
                button.addEventListener('click', function() {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    button.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                });
            }
            
            setupPasswordToggle(togglePassword, passwordInput);
            setupPasswordToggle(togglePasswordConfirmation, passwordConfirm);
            
            // Password strength indicator
            passwordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                let strength = 0;
                
                // Criteria checks
                const hasMinLength = password.length >= 8;
                const hasLetter = /[a-zA-Z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                
                // Update criteria indicators
                updateCriteria('lengthCriteria', hasMinLength);
                updateCriteria('letterCriteria', hasLetter);
                updateCriteria('numberCriteria', hasNumber);
                
                // Calculate strength
                if (hasMinLength) strength += 40;
                if (hasLetter) strength += 30;
                if (hasNumber) strength += 30;
                
                // Set strength level
                let strengthLevel = '';
                if (strength < 50) {
                    strengthLevel = 'weak';
                } else if (strength < 80) {
                    strengthLevel = 'medium';
                } else {
                    strengthLevel = 'strong';
                }
                
                passwordStrength.setAttribute('data-strength', strengthLevel);
                passwordStrength.title = strengthLevel.charAt(0).toUpperCase() + strengthLevel.slice(1);
            });
            
            function updateCriteria(id, isValid) {
                const element = document.getElementById(id);
                if (isValid) {
                    element.classList.add('valid');
                    element.innerHTML = '<i class="fas fa-check-circle"></i> ' + element.textContent;
                } else {
                    element.classList.remove('valid');
                    element.innerHTML = '<i class="fas fa-circle"></i> ' + element.textContent;
                }
            }
            
            // Password confirmation check
            function checkPasswordMatch() {
                if (passwordInput.value !== passwordConfirm.value) {
                    passwordMatch.classList.remove('hidden');
                    return false;
                } else {
                    passwordMatch.classList.add('hidden');
                    return true;
                }
            }
            
            passwordInput.addEventListener('input', checkPasswordMatch);
            passwordConfirm.addEventListener('input', checkPasswordMatch);
            
            // Form validation
            form.addEventListener('submit', function(e) {
                // Reset previous errors
                passwordMatch.classList.add('hidden');
                
                // Check password match
                if (!checkPasswordMatch()) {
                    e.preventDefault();
                    passwordConfirm.focus();
                    return;
                }
                
                // Check password meets criteria
                const hasMinLength = passwordInput.value.length >= 8;
                const hasLetter = /[a-zA-Z]/.test(passwordInput.value);
                const hasNumber = /[0-9]/.test(passwordInput.value);
                
                if (!hasMinLength || !hasLetter || !hasNumber) {
                    e.preventDefault();
                    alert('Please ensure your password meets all requirements');
                    passwordInput.focus();
                }
            });
        });
    </script>
</body>
</html>