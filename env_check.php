<?php
// Simple ENV check - paste this in Railway MySQL Query as a test
echo "Environment Variables Check:\n\n";
echo "DB_HOST: " . (getenv('DB_HOST') ?: 'NOT SET') . "\n";
echo "DB_PORT: " . (getenv('DB_PORT') ?: 'NOT SET') . "\n";
echo "DB_NAME: " . (getenv('DB_NAME') ?: 'NOT SET') . "\n";
echo "DB_USER: " . (getenv('DB_USER') ?: 'NOT SET') . "\n";
echo "DB_PASS: " . (getenv('DB_PASS') ? 'SET (hidden)' : 'NOT SET') . "\n\n";

// Try to connect
try {
    $pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_NAME'),
        getenv('DB_USER'),
        getenv('DB_PASS')
    );
    echo "✓ DATABASE CONNECTION SUCCESSFUL!\n";
} catch (Exception $e) {
    echo "✗ CONNECTION FAILED: " . $e->getMessage() . "\n";
}
?>
