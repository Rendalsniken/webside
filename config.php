<?php
/**
 * XRP Specter - Main Configuration File
 * Production-ready configuration with security and performance settings
 */

// Prevent direct access
if (!defined('XRP_SPECTER')) {
    define('XRP_SPECTER', true);
}

// Environment detection
$is_production = ($_SERVER['HTTP_HOST'] ?? 'localhost') !== 'localhost';
$is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

// Test mode - use local database or fallback
$is_test_mode = true; // Set to true for testing with Docker MySQL

// Database Configuration
if ($is_test_mode) {
    // Use Docker MySQL for testing
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'xrpspecter');
    define('DB_USER', 'xrpspecter');
    define('DB_PASS', 'xrpspecter123');
    define('DB_CHARSET', 'utf8mb4');
} else {
    // Production database
    define('DB_HOST', 'sql11.hmg9.webhuset.no');
    define('DB_NAME', '210280_xrpspecter');
    define('DB_USER', '210280_xrpspecter');
    define('DB_PASS', '249lukedo');
    define('DB_CHARSET', 'utf8mb4');
}

// Security Configuration
define('SECRET_KEY', 'xrp_specter_secret_key_2024_secure_hash');
define('SESSION_NAME', 'XRP_Specter_Session');
define('CSRF_TOKEN_NAME', 'xrp_csrf_token');
define('PASSWORD_COST', 12);

// Rate Limiting
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600); // 1 hour
define('LOGIN_ATTEMPTS_LIMIT', 5);
define('LOGIN_ATTEMPTS_WINDOW', 900); // 15 minutes

// API Configuration
define('COINGECKO_API_URL', 'https://api.coingecko.com/api/v3');
define('COINGECKO_XRP_ID', 'ripple');
define('NEWS_RSS_URL', 'https://cryptoslate.com/feed/');
define('API_CACHE_DURATION', 300); // 5 minutes

// XP System Configuration
define('XP_REGISTRATION', 50);
define('XP_LOGIN', 5);
define('XP_COMMENT', 10);
define('XP_POLL_VOTE', 5);
define('XP_NEWS_READ', 2);
define('XP_DAILY_LOGIN', 10);

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@xrpspecter.com');
define('SMTP_FROM_NAME', 'XRP Specter');

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_PATH', 'assets/img/avatars/');

// Theme Configuration
define('DEFAULT_THEME', 'dark');
define('THEME_COOKIE_NAME', 'xrp_theme');
define('THEME_COOKIE_DURATION', 31536000); // 1 year

// Error Reporting
if ($is_production) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', 'logs/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Load environment variables if .env exists
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('Dotenv\\Dotenv') && file_exists(__DIR__ . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    }
}

// Security Headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if ($is_https) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Timezone
date_default_timezone_set('UTC');

// Utility Functions
function get_config($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

function is_ajax_request() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function sanitize_output($data) {
    if (is_array($data)) {
        return array_map('sanitize_output', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function generate_csrf_token() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verify_csrf_token($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && 
           hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Set default theme if not set
if (!isset($_COOKIE[THEME_COOKIE_NAME])) {
    setcookie(THEME_COOKIE_NAME, DEFAULT_THEME, time() + THEME_COOKIE_DURATION, '/', '', $is_https, true);
}
?> 