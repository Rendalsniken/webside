<?php
/**
 * Debug Database Connection
 */

echo "<h1>Database Connection Debug</h1>";

// Test 1: Direct PDO connection
echo "<h2>Test 1: Direct PDO Connection</h2>";
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=xrpspecter;charset=utf8mb4',
        'xrpspecter',
        'xrpspecter123'
    );
    echo "<p style='color: green;'>✅ Direct PDO connection successful</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Direct PDO connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 2: Config file
echo "<h2>Test 2: Config File</h2>";
if (file_exists('config.php')) {
    echo "<p style='color: green;'>✅ config.php exists</p>";
    
    // Include config and check constants
    define('XRP_SPECTER', true);
    require_once 'config.php';
    
    echo "<p>DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "</p>";
    echo "<p>DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "</p>";
    echo "<p>DB_USER: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "</p>";
    echo "<p>DB_PASS: " . (defined('DB_PASS') ? (strlen(DB_PASS) > 0 ? 'SET' : 'EMPTY') : 'NOT DEFINED') . "</p>";
    
    // Test connection with config constants
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        echo "<p style='color: green;'>✅ Config-based connection successful</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Config-based connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ config.php not found</p>";
}

// Test 3: Database class
echo "<h2>Test 3: Database Class</h2>";
if (file_exists('db.php')) {
    echo "<p style='color: green;'>✅ db.php exists</p>";
    
    try {
        require_once 'db.php';
        $db = Database::getInstance();
        echo "<p style='color: green;'>✅ Database class instantiated successfully</p>";
        
        // Test a simple query
        $result = $db->fetch("SELECT COUNT(*) as count FROM users");
        echo "<p>Users count: " . $result['count'] . "</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Database class failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ db.php not found</p>";
}

// Test 4: Auth class
echo "<h2>Test 4: Auth Class</h2>";
if (file_exists('auth.php')) {
    echo "<p style='color: green;'>✅ auth.php exists</p>";
    
    try {
        session_start();
        $auth = Auth::getInstance();
        echo "<p style='color: green;'>✅ Auth class instantiated successfully</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Auth class failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ auth.php not found</p>";
}

echo "<h2>Environment Info</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>PDO MySQL: " . (extension_loaded('pdo_mysql') ? 'Loaded' : 'Not loaded') . "</p>";
echo "<p>Current working directory: " . getcwd() . "</p>";
?> 