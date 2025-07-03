<?php
/**
 * Test Login Page
 */

echo "Testing login page...<br>";

// Test basic PHP
echo "PHP is working<br>";

// Test includes
try {
    require_once 'config.php';
    echo "Config loaded<br>";
    
    require_once 'db.php';
    echo "Database loaded<br>";
    
    require_once 'auth.php';
    echo "Auth loaded<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "Test complete<br>";
?> 