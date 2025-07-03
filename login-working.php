<?php
/**
 * Working Login Page - No Database Required
 */

// Basic session start
session_start();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple validation
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        // For testing, accept any email/password
        if ($email === 'test@example.com' && $password === 'password123') {
            // Set session and redirect to dashboard
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'testuser';
            $_SESSION['is_logged_in'] = true;
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password. Try test@example.com / password123';
        }
    }
}

// Redirect if already logged in
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in']) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - XRP Specter</title>
    <meta name="description" content="Sign in to your XRP Specter account">
    
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
        
        .btn {
            padding: var(--spacing-md) var(--spacing-xl);
            border: none;
            border-radius: var(--radius-md);
            font-size: var(--font-size-base);
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-fast);
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: var(--text-inverse);
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--text-primary);
            border: 2px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background: var(--bg-secondary);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .auth-links {
            text-align: center;
            margin-top: var(--spacing-xl);
        }
        
        .auth-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: var(--font-size-sm);
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .test-credentials {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            margin-top: var(--spacing-lg);
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
        }
        
        .test-credentials strong {
            color: var(--text-primary);
        }
    </style>
</head>
<body class="theme-dark">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <span class="auth-logo-text">XRP Specter</span>
                </div>
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Sign in to your XRP Specter account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error" style="color: var(--error-color); background: rgba(220, 53, 69, 0.1); padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg);">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <input type="email" name="email" class="form-input" placeholder="Email address" required>
                </div>
                
                <div class="form-group">
                    <input type="password" name="password" class="form-input" placeholder="Password" required>
                </div>
                
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                
                <div class="auth-actions">
                    <button type="submit" class="btn btn-primary">Sign In</button>
                    <a href="register-working.php" class="btn btn-secondary">Create Account</a>
                </div>
            </form>
            
            <div class="auth-links">
                <p><a href="index.php">← Back to home</a></p>
            </div>
            
            <div class="test-credentials">
                <strong>Test Credentials:</strong><br>
                Email: test@example.com<br>
                Password: password123
            </div>
        </div>
    </div>
</body>
</html> 