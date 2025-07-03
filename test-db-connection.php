<?php
/**
 * Test Database Connection
 * Verify connection to the external database
 */

define('XRP_SPECTER', true);
require_once 'config.php';
require_once 'db.php';

echo "<h1>Database Connection Test</h1>";

try {
    // Test database connection
    $db = db();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test if tables exist
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h2>Existing Tables:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>{$table}</li>";
    }
    echo "</ul>";
    
    // Test user count
    $user_count = $db->fetch("SELECT COUNT(*) as count FROM users")['count'] ?? 0;
    echo "<p>Total users: {$user_count}</p>";
    
    // Test if we can insert a test record
    $test_data = [
        'username' => 'test_user_' . time(),
        'email' => 'test@example.com',
        'password_hash' => password_hash('test123', PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $test_id = $db->insert('users', $test_data);
    if ($test_id) {
        echo "<p style='color: green;'>✅ Test insert successful (ID: {$test_id})</p>";
        
        // Clean up test data
        $db->delete('users', 'id = ?', [$test_id]);
        echo "<p style='color: blue;'>✅ Test data cleaned up</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Configuration:</h2>";
echo "<ul>";
echo "<li>Host: " . DB_HOST . "</li>";
echo "<li>Database: " . DB_NAME . "</li>";
echo "<li>User: " . DB_USER . "</li>";
echo "<li>Charset: " . DB_CHARSET . "</li>";
echo "</ul>";
?> 