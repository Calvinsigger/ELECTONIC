<?php
/**
 * PASSWORD HASH GENERATOR
 * 
 * This script helps you generate bcrypt password hashes for your admin/test accounts.
 * You can then copy the hash and manually insert it into the database.
 */

// Only run this in command line or with direct access
if (php_sapi_name() === 'cli' || isset($_GET['generate'])) {
    
    $password = $_GET['password'] ?? 'test123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    echo "Password: " . htmlspecialchars($password) . "\n";
    echo "Hash: " . htmlspecialchars($hash) . "\n\n";
    
    // Verify it works
    if (password_verify($password, $hash)) {
        echo "✓ Hash verification: SUCCESS\n";
    } else {
        echo "✗ Hash verification: FAILED\n";
    }
    
} else {
    echo "This script generates bcrypt password hashes.\n";
    echo "Usage: php password_hash_generator.php\n";
    echo "Or access via web: ?generate=1&password=yourpassword\n";
}
?>
