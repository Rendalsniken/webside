<?php
echo "<h1>Simple Test</h1>";

// Test 1: Basic PHP
echo "<p>✅ PHP is working</p>";

// Test 2: Config file
if (file_exists('config.php')) {
    echo "<p>✅ config.php exists</p>";
    
    // Include config
    define('XRP_SPECTER', true);
    require_once 'config.php';
    
    echo "<p>✅ config.php loaded</p>";
    echo "<p>DB_HOST: " . DB_HOST . "</p>";
    echo "<p>DB_USER: " . DB_USER . "</p>";
    echo "<p>DB_NAME: " . DB_NAME . "</p>";
    
} else {
    echo "<p>❌ config.php not found</p>";
}

// Test 3: Database connection
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS
    );
    echo "<p>✅ Database connection successful</p>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p>Users in database: " . $result['count'] . "</p>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p>✅ Test complete</p>";
?> 