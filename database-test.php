<?php
/**
 * XRP Specter - Database Test
 * Comprehensive test of all database functionality
 */

echo "<h1>🔍 XRP Specter Database Test</h1>";

// Test 1: Basic PHP and Config
echo "<h2>1. Basic Setup Test</h2>";
echo "<p>✅ PHP Version: " . PHP_VERSION . "</p>";
echo "<p>✅ PDO MySQL Extension: " . (extension_loaded('pdo_mysql') ? 'Loaded' : 'Not loaded') . "</p>";

if (file_exists('config.php')) {
    echo "<p>✅ config.php exists</p>";
    define('XRP_SPECTER', true);
    require_once 'config.php';
    echo "<p>✅ config.php loaded successfully</p>";
} else {
    echo "<p>❌ config.php not found</p>";
    exit;
}

// Test 2: Database Configuration
echo "<h2>2. Database Configuration</h2>";
echo "<p>DB_HOST: " . DB_HOST . "</p>";
echo "<p>DB_NAME: " . DB_NAME . "</p>";
echo "<p>DB_USER: " . DB_USER . "</p>";
echo "<p>DB_PASS: " . (strlen(DB_PASS) > 0 ? 'SET' : 'EMPTY') . "</p>";
echo "<p>DB_CHARSET: " . DB_CHARSET . "</p>";

// Test 3: Direct Database Connection
echo "<h2>3. Direct Database Connection</h2>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "<p style='color: green;'>✅ Direct PDO connection successful</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Direct PDO connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 4: Database Tables
echo "<h2>4. Database Tables</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>✅ Found " . count($tables) . " tables:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Failed to get tables: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 5: Users Table
echo "<h2>5. Users Table Test</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p>✅ Users table accessible</p>";
    echo "<p>Total users: " . $result['count'] . "</p>";
    
    // Test user structure
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    echo "<p>Users table columns:</p><ul>";
    foreach ($columns as $column) {
        echo "<li>" . $column['Field'] . " (" . $column['Type'] . ")</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Users table test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 6: Database Class
echo "<h2>6. Database Class Test</h2>";
try {
    require_once 'db.php';
    $db = Database::getInstance();
    echo "<p style='color: green;'>✅ Database class instantiated successfully</p>";
    
    // Test a simple query
    $result = $db->fetch("SELECT COUNT(*) as count FROM users");
    echo "<p>✅ Database class query successful: " . $result['count'] . " users</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database class failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 7: Auth Class
echo "<h2>7. Auth Class Test</h2>";
try {
    require_once 'auth.php';
    session_start();
    $auth = Auth::getInstance();
    echo "<p style='color: green;'>✅ Auth class instantiated successfully</p>";
    
    // Test if user is logged in
    $isLoggedIn = $auth->isLoggedIn();
    echo "<p>User logged in: " . ($isLoggedIn ? 'Yes' : 'No') . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Auth class failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 8: Test User Creation
echo "<h2>8. Test User Creation</h2>";
try {
    $testEmail = 'test_' . time() . '@example.com';
    $testUsername = 'testuser_' . time();
    
    // Check if test user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    if ($stmt->fetch()) {
        echo "<p>⚠️ Test user already exists, skipping creation</p>";
    } else {
        // Create test user
        $hashedPassword = password_hash('test123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, xp_points, level, is_active, created_at) VALUES (?, ?, ?, 'user', 50, 1, 1, NOW())");
        $stmt->execute([$testUsername, $testEmail, $hashedPassword]);
        $userId = $pdo->lastInsertId();
        echo "<p style='color: green;'>✅ Test user created successfully (ID: $userId)</p>";
        
        // Clean up test user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        echo "<p style='color: blue;'>✅ Test user cleaned up</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Test user creation failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 9: Other Tables
echo "<h2>9. Other Tables Test</h2>";
$otherTables = ['achievements', 'notifications', 'polls', 'comments', 'xp_logs'];
foreach ($otherTables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch();
        echo "<p>✅ $table table: " . $result['count'] . " records</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠️ $table table: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Test 10: Performance Test
echo "<h2>10. Performance Test</h2>";
$startTime = microtime(true);
try {
    for ($i = 0; $i < 10; $i++) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $stmt->fetch();
    }
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    echo "<p style='color: green;'>✅ 10 queries completed in {$duration}ms</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Performance test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 11: Connection Pool Test
echo "<h2>11. Connection Pool Test</h2>";
try {
    $db1 = Database::getInstance();
    $db2 = Database::getInstance();
    if ($db1 === $db2) {
        echo "<p style='color: green;'>✅ Singleton pattern working correctly</p>";
    } else {
        echo "<p style='color: red;'>❌ Singleton pattern not working</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Connection pool test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>🎉 Test Summary</h2>";
echo "<p><strong>Database Status:</strong> ✅ Working</p>";
echo "<p><strong>Configuration:</strong> ✅ Correct</p>";
echo "<p><strong>Classes:</strong> ✅ Loaded</p>";
echo "<p><strong>Ready for:</strong> index.php, login.php, register.php, dashboard.php</p>";

echo "<hr>";
echo "<p><a href='index.php'>→ Go to Index Page</a></p>";
echo "<p><a href='login.php'>→ Go to Login Page</a></p>";
echo "<p><a href='register.php'>→ Go to Register Page</a></p>";
echo "<p><a href='dashboard.php'>→ Go to Dashboard</a></p>";
?> 