<?php
/**
 * Test Docker MySQL Database Connection
 */

echo "<h1>Docker MySQL Database Connection Test</h1>";

try {
    // Test Docker MySQL connection
    $pdo = new PDO(
        'mysql:host=localhost;dbname=xrpspecter;charset=utf8mb4',
        'xrpspecter',
        'xrpspecter123',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    echo "<p style='color: green;'>✅ Docker MySQL connection successful!</p>";
    
    // Test tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Tables in xrpspecter database:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Test users table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "<p>Total users: $userCount</p>";
    
    // Test insert
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute(['testuser', 'test@example.com', password_hash('test123', PASSWORD_DEFAULT)]);
    $testId = $pdo->lastInsertId();
    echo "<p style='color: green;'>✅ Test insert successful (ID: $testId)</p>";
    
    // Clean up test data
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$testId]);
    echo "<p style='color: blue;'>✅ Test data cleaned up</p>";
    
    echo "<h2>Configuration:</h2>";
    echo "<ul>";
    echo "<li>Host: localhost</li>";
    echo "<li>Database: xrpspecter</li>";
    echo "<li>User: xrpspecter</li>";
    echo "<li>Charset: utf8mb4</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h2>Error Details:</h2>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?> 