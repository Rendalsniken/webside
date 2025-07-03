<?php
/**
 * XRP Specter - Login Page
 * Secure user authentication with modern design
 */

define('XRP_SPECTER', true);
require_once 'config.php';

if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

$error = '';
$success = '';

// Check for logout message
if (isset($_GET['message']) && $_GET['message'] === 'logged_out') {
    $success = 'You have been successfully logged out.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    try {
        // Load auth system only when needed
        require_once 'db.php';
        require_once 'auth.php';
        
        // Verify CSRF token
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please try again.';
        } else {
            $auth = Auth::getInstance();
            $result = $auth->login($email, $password, $remember);
            
            if ($result['success']) {
                // Redirect to dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error = $result['message'];
            }
        }
    } catch (Exception $e) {
        $error = 'Database connection error. Please try again later.';
        error_log("Login error: " . $e->getMessage());
    }
}

// Redirect if already logged in
try {
    require_once 'db.php';
    require_once 'auth.php';
    if (is_logged_in()) {
        header('Location: dashboard.php');
        exit;
    }
} catch (Exception $e) {
    // If database fails, just continue to login page
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - XRP Specter</title>
    <meta name="description" content="Sign in to your XRP Specter account">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Additional styles for login page -->
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color) 0%, #0099CC 100%);
            padding: var(--spacing-lg);
        }
        
        .auth-card {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            padding: var(--spacing-xxl);
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }
        
        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), #0099CC);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: var(--spacing-xxl);
        }
        
        .auth-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-lg);
        }
        
        .auth-logo img {
            width: 48px;
            height: 48px;
        }
        
        .auth-logo-text {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), #0099CC);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .auth-title {
            font-size: var(--font-size-xl);
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--spacing-sm);
        }
        
        .auth-subtitle {
            color: var(--text-secondary);
            font-size: var(--font-size-base);
        }
        
        .auth-form {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
        }
        
        .form-group {
            position: relative;
        }
        
        .form-input {
            width: 100%;
            padding: var(--spacing-md);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: var(--font-size-base);
            transition: all var(--transition-fast);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
        }
        
        .form-input.error {
            border-color: var(--error-color);
        }
        
        .form-label {
            position: absolute;
            top: var(--spacing-md);
            left: var(--spacing-md);
            color: var(--text-secondary);
            transition: all var(--transition-fast);
            pointer-events: none;
            background: var(--bg-primary);
            padding: 0 var(--spacing-xs);
        }
        
        .form-input:focus + .form-label,
        .form-input:not(:placeholder-shown) + .form-label {
            top: -8px;
            left: var(--spacing-sm);
            font-size: var(--font-size-sm);
            color: var(--primary-color);
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        
        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary-color);
        }
        
        .remember-me label {
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
            cursor: pointer;
        }
        
        .auth-actions {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), #0099CC);
            color: white;
            border: none;
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: var(--font-size-base);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .auth-links {
            text-align: center;
            margin-top: var(--spacing-lg);
        }
        
        .auth-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: var(--font-size-sm);
            transition: color var(--transition-fast);
        }
        
        .auth-links a:hover {
            color: var(--primary-hover);
        }
        
        .divider {
            margin: var(--spacing-lg) 0;
            text-align: center;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--border-color);
        }
        
        .divider span {
            background: var(--bg-card);
            padding: 0 var(--spacing-md);
            color: var(--text-secondary);
            font-size: var(--font-size-sm);
        }
        
        .error-message {
            background: rgba(220, 53, 69, 0.1);
            color: var(--error-color);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            border: 1px solid rgba(220, 53, 69, 0.2);
            font-size: var(--font-size-sm);
            text-align: center;
        }
        
        .success-message {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            border: 1px solid rgba(40, 167, 69, 0.2);
            font-size: var(--font-size-sm);
            text-align: center;
        }
        
        .password-toggle {
            position: absolute;
            right: var(--spacing-md);
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: var(--spacing-xs);
            border-radius: var(--radius-sm);
            transition: all var(--transition-fast);
        }
        
        .password-toggle:hover {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }
        
        /* Header styles for auth pages */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
        }
        
        .auth-container {
            margin-top: 80px;
        }
        
        @media (max-width: 480px) {
            .auth-card {
                padding: var(--spacing-lg);
                margin: var(--spacing-md);
            }
            
            .auth-logo-text {
                font-size: var(--font-size-xl);
            }
            
            .auth-container {
                margin-top: 70px;
            }
        }
    </style>
</head>
<body class="theme-dark">
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <div class="header-left">
                <div class="logo">
                    <a href="index.php" class="logo-text">XRP Specter</a>
                </div>
            </div>
            
            <div class="header-right">
                <!-- Theme Toggle -->
                <button class="theme-toggle" id="theme-toggle" aria-label="Toggle theme">
                    <svg class="theme-icon" viewBox="0 0 24 24">
                        <path class="sun-path" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        <path class="moon-path" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>
                
                <!-- Auth Buttons -->
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-secondary active">Login</a>
                    <a href="register.php" class="btn btn-primary">Join Now</a>
                </div>
            </div>
        </div>
    </header>

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <img src="assets/img/logo.png" alt="XRP Specter">
                    <span class="auth-logo-text">XRP Specter</span>
                </div>
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Sign in to your account to continue</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <form class="auth-form" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                
                <div class="form-group">
                    <input type="email" id="email" name="email" class="form-input" placeholder=" " required>
                    <label for="email" class="form-label">Email Address</label>
                </div>
                
                <div class="form-group">
                    <input type="password" id="password" name="password" class="form-input" placeholder=" " required>
                    <label for="password" class="form-label">Password</label>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                    </button>
                </div>
                
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me for 30 days</label>
                </div>
                
                <div class="auth-actions">
                    <button type="submit" class="btn-login" id="login-btn">
                        <span class="btn-text">Sign In</span>
                        <span class="btn-loading" style="display: none;">
                            <svg class="loading-spinner" width="16" height="16" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                                    <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                                    <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                                </circle>
                            </svg>
                        </span>
                    </button>
                </div>
            </form>
            
            <div class="divider">
                <span>or</span>
            </div>
            
            <div class="auth-links">
                <a href="forgot-password.php">Forgot your password?</a>
                <br>
                <span style="color: var(--text-secondary);">Don't have an account? </span>
                <a href="register.php">Sign up</a>
            </div>
        </div>
    </div>
    
    <script>
        // Password toggle functionality
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.password-toggle svg');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.innerHTML = `
                    <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                `;
            } else {
                passwordInput.type = 'password';
                toggleBtn.innerHTML = `
                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                `;
            }
        }
        
        // Form submission handling
        document.querySelector('.auth-form').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('login-btn');
            const btnText = loginBtn.querySelector('.btn-text');
            const btnLoading = loginBtn.querySelector('.btn-loading');
            
            // Show loading state
            loginBtn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-block';
        });
        
        // Auto-focus email field
        document.getElementById('email').focus();
        
        // Theme detection
        const savedTheme = localStorage.getItem('xrp_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</body>
</html> 