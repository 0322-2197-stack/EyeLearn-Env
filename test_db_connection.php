<?php
/**
 * Database Connection Test
 * Tests the connection to the configured database
 */

require_once __DIR__ . '/database/db_connection.php';

echo "Testing Database Connection...\n\n";

echo "Current Configuration:\n";
echo "- DB_HOST: " . getenv('DB_HOST') . "\n";
echo "- DB_PORT: " . getenv('DB_PORT') . "\n";
echo "- DB_USER: " . getenv('DB_USER') . "\n";
echo "- DB_NAME: " . getenv('DB_NAME') . "\n";
echo "- DB_PASS: " . (getenv('DB_PASS') ? '***' . substr(getenv('DB_PASS'), -4) : 'not set') . "\n\n";

try {
    echo "Attempting PDO connection...\n";
    $pdo = getPDOConnection();
    echo "✓ PDO Connection successful!\n\n";
    
    // Test query
    $stmt = $pdo->query("SELECT DATABASE() as current_db, VERSION() as mysql_version");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Connected to database: " . $result['current_db'] . "\n";
    echo "MySQL version: " . $result['mysql_version'] . "\n\n";
    
    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Available tables (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✓ All tests passed!\n";
?>
