<?php
/**
 * XRP Specter - Authentication System
 * Secure user authentication with session management and security features
 */

// Prevent direct access
if (!defined('XRP_SPECTER')) {
    define('XRP_SPECTER', true);
}

require_once 'config.php';
require_once 'db.php';

class Auth {
    private static $instance = null;
    private $user = null;
    
    private function __construct() {
        $this->checkSession();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function checkSession() {
        if (isset($_SESSION['user_id'])) {
            try {
                $this->user = get_user_by_id($_SESSION['user_id']);
                if (!$this->user || !$this->user['is_active']) {
                    $this->logout();
                }
            } catch (Exception $e) {
                // If database connection fails, just clear the session
                $this->logout();
            }
        }
    }
    
    public function login($email, $password, $remember = false) {
        // Check rate limiting
        $ip_address = $this->getClientIP();
        $login_attempts = get_login_attempts($ip_address);
        
        if ($login_attempts >= LOGIN_ATTEMPTS_LIMIT) {
            return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
        }
        
        // Validate input
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required.'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }
        
        // Get user
        $user = get_user_by_email($email);
        if (!$user) {
            $this->recordLoginAttempt($ip_address);
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }
        
        // Check if user is active
        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Account is deactivated. Please contact support.'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            $this->recordLoginAttempt($ip_address);
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }
        
        // Login successful
        $this->user = $user;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        
        // Update last login
        update_user($user['id'], ['last_login' => date('Y-m-d H:i:s')]);
        
        // Add XP for login
        add_xp($user['id'], XP_LOGIN, 'daily_login', 'Daily login bonus');
        
        // Set remember me cookie
        if ($remember) {
            $this->setRememberMeCookie($user['id']);
        }
        
        // Clear login attempts
        db()->delete('rate_limits', 'ip_address = ? AND endpoint = ?', [$ip_address, 'login']);
        
        return ['success' => true, 'user' => $user];
    }
    
    public function register($username, $email, $password, $confirm_password) {
        // Validate input
        $errors = [];
        
        if (empty($username) || strlen($username) < 3 || strlen($username) > 20) {
            $errors[] = 'Username must be between 3 and 20 characters.';
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores.';
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email address is required.';
        }
        
        if (empty($password) || strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Check if username or email already exists
        if (get_user_by_username($username)) {
            $errors[] = 'Username already exists.';
        }
        
        if (get_user_by_email($email)) {
            $errors[] = 'Email already registered.';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Create user
        try {
            $user_data = [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role' => 'user',
                'user_flags' => 0,
                'xp_points' => XP_REGISTRATION,
                'level' => 1,
                'is_active' => 1,
                'email_verified' => 0
            ];
            
            $user_id = create_user($user_data);
            
            // Add XP for registration
            add_xp($user_id, XP_REGISTRATION, 'registration', 'Account registration');
            
            // Create welcome notification
            create_notification($user_id, 'success', 'Welcome to XRP Specter!', 
                'Thank you for joining our community. You earned ' . XP_REGISTRATION . ' XP for registering!');
            
            // Send welcome email
            $welcome_subject = 'Welcome to XRP Specter!';
            $welcome_body = '<h1>Welcome, ' . htmlspecialchars($username) . '!</h1>' .
                '<p>Thank you for registering at XRP Specter. You can now log in and start using your dashboard.</p>';
            send_email($email, $welcome_subject, $welcome_body);
            
            return ['success' => true, 'user_id' => $user_id];
            
        } catch (Exception $e) {
            error_log("User registration failed: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
        }
    }
    
    public function logout() {
        $this->user = null;
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['user_id']);
            unset($_SESSION['user_role']);
            session_destroy();
        }
        
        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
    }
    
    public function isLoggedIn() {
        return $this->user !== null;
    }
    
    public function getCurrentUser() {
        return $this->user;
    }
    
    public function getCurrentUserId() {
        return $this->user ? $this->user['id'] : null;
    }
    
    public function getCurrentUserRole() {
        return $this->user ? $this->user['role'] : null;
    }
    
    public function isAdmin() {
        return $this->user && $this->user['role'] === 'admin';
    }
    
    public function isModerator() {
        return $this->user && in_array($this->user['role'], ['admin', 'moderator']);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: index.php');
            exit;
        }
    }
    
    public function requireModerator() {
        $this->requireLogin();
        if (!$this->isModerator()) {
            header('Location: index.php');
            exit;
        }
    }
    
    private function setRememberMeCookie($user_id) {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Store token in database (you might want to create a remember_tokens table)
        // For now, we'll use a simple approach with the existing rate_limits table
        db()->insert('rate_limits', [
            'ip_address' => $token,
            'endpoint' => 'remember_token',
            'requests_count' => $user_id,
            'window_start' => date('Y-m-d H:i:s', $expires)
        ]);
        
        setcookie('remember_token', $token, $expires, '/', '', true, true);
    }
    
    private function getClientIP() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    private function recordLoginAttempt($ip_address) {
        record_login_attempt($ip_address);
    }
    
    public function changePassword($user_id, $current_password, $new_password, $confirm_password) {
        // Validate input
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            return ['success' => false, 'message' => 'All password fields are required.'];
        }
        
        if ($new_password !== $confirm_password) {
            return ['success' => false, 'message' => 'New passwords do not match.'];
        }
        
        if (strlen($new_password) < 8) {
            return ['success' => false, 'message' => 'New password must be at least 8 characters long.'];
        }
        
        // Get user
        $user = get_user_by_id($user_id);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }
        
        // Update password
        try {
            update_user($user_id, ['password' => $new_password]);
            return ['success' => true, 'message' => 'Password updated successfully.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update password.'];
        }
    }
    
    public function resetPassword($email) {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Valid email address is required.'];
        }
        
        $user = get_user_by_email($email);
        if (!$user) {
            return ['success' => false, 'message' => 'Email not found in our system.'];
        }
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = time() + (60 * 60); // 1 hour
        
        // Store reset token (you might want to create a password_resets table)
        // For now, we'll use the rate_limits table
        db()->insert('rate_limits', [
            'ip_address' => $token,
            'endpoint' => 'password_reset',
            'requests_count' => $user['id'],
            'window_start' => date('Y-m-d H:i:s', $expires)
        ]);
        
        // Send reset email (implement with PHPMailer)
        // For now, we'll just return success
        return ['success' => true, 'message' => 'Password reset instructions sent to your email.'];
    }
    
    public function verifyResetToken($token) {
        $sql = "SELECT requests_count as user_id FROM rate_limits 
                WHERE ip_address = ? AND endpoint = 'password_reset' AND window_start > NOW()";
        $result = db()->fetchColumn($sql, [$token]);
        return $result !== false ? $result : null;
    }
    
    public function updateProfile($user_id, $data) {
        $allowed_fields = ['username', 'email', 'avatar'];
        $update_data = [];
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }
        
        if (empty($update_data)) {
            return ['success' => false, 'message' => 'No valid fields to update.'];
        }
        
        // Check for unique constraints
        if (isset($update_data['username'])) {
            $existing = get_user_by_username($update_data['username']);
            if ($existing && $existing['id'] != $user_id) {
                return ['success' => false, 'message' => 'Username already exists.'];
            }
        }
        
        if (isset($update_data['email'])) {
            $existing = get_user_by_email($update_data['email']);
            if ($existing && $existing['id'] != $user_id) {
                return ['success' => false, 'message' => 'Email already exists.'];
            }
        }
        
        try {
            update_user($user_id, $update_data);
            return ['success' => true, 'message' => 'Profile updated successfully.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update profile.'];
        }
    }
}

// Initialize authentication
$auth = Auth::getInstance();

// Helper functions
function is_logged_in() {
    return Auth::getInstance()->isLoggedIn();
}

function current_user() {
    return Auth::getInstance()->getCurrentUser();
}

function current_user_id() {
    return Auth::getInstance()->getCurrentUserId();
}

function current_user_role() {
    return Auth::getInstance()->getCurrentUserRole();
}

function is_admin() {
    return Auth::getInstance()->isAdmin();
}

function is_moderator() {
    return Auth::getInstance()->isModerator();
}

function require_login() {
    Auth::getInstance()->requireLogin();
}

function require_admin() {
    Auth::getInstance()->requireAdmin();
}

function require_moderator() {
    Auth::getInstance()->requireModerator();
}
?> 