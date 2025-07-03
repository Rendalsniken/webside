<?php
/**
 * Simple Login Test
 */

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<title>Simple Login Test</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; padding: 20px; }";
echo ".form-group { margin: 10px 0; }";
echo "input { padding: 10px; margin: 5px; }";
echo "button { padding: 10px 20px; background: #007bff; color: white; border: none; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>Simple Login Test</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p>Form submitted!</p>";
    echo "<p>Email: " . htmlspecialchars($_POST['email'] ?? '') . "</p>";
    echo "<p>Password: " . str_repeat('*', strlen($_POST['password'] ?? '')) . "</p>";
} else {
    echo "<form method='POST'>";
    echo "<div class='form-group'>";
    echo "<label>Email: <input type='email' name='email' required></label>";
    echo "</div>";
    echo "<div class='form-group'>";
    echo "<label>Password: <input type='password' name='password' required></label>";
    echo "</div>";
    echo "<button type='submit'>Login</button>";
    echo "</form>";
}

echo "</body>";
echo "</html>";
?> 