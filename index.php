<?php
if (!isset($_GET['from']) && !isset($_GET['signup'])) {
    header("Location: main.php");
    exit();
}


session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';

// Generate CSRF token for each form load
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Redirect logged-in users
if (is_logged_in()) {
    redirect_to_dashboard();
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>DigiTech | <?php echo isset($_GET['signup']) ? 'Sign Up' : 'Login'; ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #2563eb;
      --primary-dark: #1e40af;
      --secondary: #3b82f6;
      --background: #f8fafc;
      --card-bg: #ffffff;
      --text: #1e293b;
      --text-light: #64748b;
      --input-border: #e2e8f0;
      --input-focus: #bfdbfe;
      --error: #dc2626;
      --success: #16a34a;
      --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      --radius: 0.5rem;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }

    body {
      background: var(--background);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 1rem;
      color: var(--text);
      line-height: 1.5;
    }

    .container {
      background: var(--card-bg);
      padding: 2rem;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      width: 100%;
      max-width: 28rem;
      text-align: center;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .container:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .logo {
      width: 4rem;
      margin-bottom: 1rem;
    }

    h2 {
      margin-bottom: 1.5rem;
      color: var(--primary);
      font-weight: 600;
      font-size: 1.5rem;
    }

    .form-message {
      padding: 0.75rem;
      border-radius: var(--radius);
      margin-bottom: 1.25rem;
      font-size: 0.875rem;
    }

    .error-message {
      background-color: #fee2e2;
      color: var(--error);
      border-left: 4px solid var(--error);
    }

    .success-message {
      background-color: #dcfce7;
      color: var(--success);
      border-left: 4px solid var(--success);
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .input-group {
      position: relative;
    }

    .input-group i {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-light);
    }

    input {
      width: 100%;
      padding: 0.875rem 1rem 0.875rem 2.5rem;
      border: 1px solid var(--input-border);
      border-radius: var(--radius);
      font-size: 0.9375rem;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px var(--input-focus);
    }

    .password-toggle {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--text-light);
      cursor: pointer;
    }

    button {
      background: var(--primary);
      color: white;
      padding: 0.875rem;
      border: none;
      border-radius: var(--radius);
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.2s ease, transform 0.1s ease;
    }

    button:hover {
      background: var(--primary-dark);
    }

    button:active {
      transform: scale(0.98);
    }

    .switch {
      margin-top: 1rem;
      font-size: 0.875rem;
      color: var(--text-light);
    }

    .switch a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 500;
      margin-left: 0.25rem;
    }

    .switch a:hover {
      text-decoration: underline;
    }

    .remember-me {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.875rem;
      color: var(--text-light);
    }

    .remember-me input {
      width: auto;
      padding: 0;
      margin: 0;
    }

    @media (max-width: 480px) {
      .container {
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h2><?php echo isset($_GET['signup']) ? 'Create an Account' : 'Welcome Back'; ?></h2>
    
    <?php if (isset($_GET['error'])): ?>
      <div class="form-message error-message">
        <?php
        $error_messages = [
          'invalid_credentials' => 'Invalid email or password',
          'user_exists' => 'Email already registered',
          'db_error' => 'Database error occurred',
          'invalid_input' => 'Please fill all fields correctly',
          'csrf_failed' => 'Security token expired. Please try again.'
        ];
        echo $error_messages[$_GET['error']] ?? 'An error occurred';
        ?>
      </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
      <div class="form-message success-message">
        <?php echo $_GET['success'] === 'signup' ? 'Registration successful! Please log in.' : ''; ?>
      </div>
    <?php endif; ?>
    
    <form action="process.php" method="post">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
      
      <?php if (isset($_GET['signup'])): ?>
        <div class="input-group">
          <i class="fas fa-user"></i>
          <input type="text" name="name" placeholder="Full Name" required minlength="2">
        </div>
        
        <div class="input-group">
          <i class="fas fa-envelope"></i>
          <input type="email" name="email" placeholder="Email Address" required>
        </div>
        
        <div class="input-group">
          <i class="fas fa-lock"></i>
          <input type="password" name="password" id="password" placeholder="Create Password" required minlength="8">
          <button type="button" class="password-toggle" aria-label="Toggle password visibility">
            <i class="fas fa-eye"></i>
          </button>
        </div>
        
        <button type="submit" name="action" value="signup">Sign Up</button>
        
        <div class="switch">
          Already registered? <a href="index.php">Log in</a>
        </div>
      <?php else: ?>
        <div class="input-group">
          <i class="fas fa-envelope"></i>
          <input type="email" name="email" placeholder="Email Address" required 
                 value="<?php echo isset($_COOKIE['remember_email']) ? htmlspecialchars($_COOKIE['remember_email']) : ''; ?>">
        </div>
        
        <div class="input-group">
          <i class="fas fa-lock"></i>
          <input type="password" name="password" id="password" placeholder="Password" required>
          <button type="button" class="password-toggle" aria-label="Toggle password visibility">
            <i class="fas fa-eye"></i>
          </button>
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <label class="remember-me">
            <input type="checkbox" name="remember_me" <?php echo isset($_COOKIE['remember_email']) ? 'checked' : ''; ?>>
            Remember me
          </label>
          <a href="user/forgot-password-visual.php" style="font-size: 0.875rem; color: var(--primary); text-decoration: none;">Forgot password?</a>
        </div>
        
        <button type="submit" name="action" value="login">Log In</button>
        
        <div class="switch">
          New here? <a href="index.php?signup=1">Create an account</a>
        </div>
      <?php endif; ?>
    </form>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Toggle password visibility
      const toggleButtons = document.querySelectorAll('.password-toggle');
      
      toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
          const input = this.parentElement.querySelector('input');
          const icon = this.querySelector('i');
          
          if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
          } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
          }
        });
      });
      
      // Form validation
      const forms = document.querySelectorAll('form');
      forms.forEach(form => {
        form.addEventListener('submit', function(e) {
          const inputs = this.querySelectorAll('input[required]');
          let isValid = true;
          
          inputs.forEach(input => {
            if (!input.value.trim()) {
              input.style.borderColor = 'var(--error)';
              isValid = false;
            }
          });
          
          if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            const firstInvalid = this.querySelector('input[required]:invalid');
            if (firstInvalid) {
              firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
              firstInvalid.focus();
            }
          }
        });
        
        // Reset input styles when typing
        const inputs = form.querySelectorAll('input');
        inputs.forEach(input => {
          input.addEventListener('input', function() {
            this.style.borderColor = '';
          });
        });
      });
    });
  </script>
</body>
</html>