<?php
/**
 * Restore module_parts data
 */

echo "Restoring module_parts to Railway...\n\n";

require_once __DIR__ . '/user/load_env.php';

try {
    $dsn = "mysql:host=" . getenv('DB_HOST') . ";port=" . getenv('DB_PORT') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8mb4";
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to Railway database\n\n";
    
    // Insert the module part that was accidentally deleted
    echo "Inserting module part...\n";
    
    $sql = "INSERT INTO module_parts (id, module_id, title, content, created_at) 
            VALUES (57, 22, 'Cybersecurity Challenges', '', NOW())";
    
    $pdo->exec($sql);
    
    echo "✓ Module part inserted: ID 57 - Cybersecurity Challenges\n\n";
    
    // Verify
    echo "Verifying module_parts...\n";
    $stmt = $pdo->query("SELECT * FROM module_parts");
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($parts) . " module part(s):\n";
    foreach ($parts as $part) {
        echo "  - Part ID {$part['id']}: {$part['title']} (Module ID: {$part['module_id']})\n";
    }
    
    echo "\n✅ Module parts restored successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
