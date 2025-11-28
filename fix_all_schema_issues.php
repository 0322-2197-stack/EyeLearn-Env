<?php
/**
 * Fix all missing tables and prepare() errors
 */

echo "Fixing all database schema issues...\n\n";

require_once __DIR__ . '/user/load_env.php';

try {
    $dsn = "mysql:host=" . getenv('DB_HOST') . ";port=" . getenv('DB_PORT') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8mb4";
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to Railway database\n\n";
    
    // Create user_progress table if missing
    echo "Checking for user_progress table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `user_progress` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `module_id` int(11) NOT NULL,
      `completion_percentage` decimal(5,2) DEFAULT 0.00,
      `last_accessed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `user_module`(`user_id`, `module_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "✓ user_progress table ready\n";
    
    // Add module_id column to quiz_results if missing
    echo "Checking quiz_results table structure...\n";
    try {
        $pdo->exec("ALTER TABLE quiz_results ADD COLUMN `module_id` INT(11) NULL AFTER `user_id`");
        echo "✓ Added module_id to quiz_results\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "  ℹ module_id already exists in quiz_results\n";
        } else {
            echo "  ⚠ Could  not add module_id: " . $e->getMessage() . "\n";
        }
    }
    
    // Add module_id column to retake_results if missing
    echo "Checking retake_results table structure...\n";
    try {
        $pdo->exec("ALTER TABLE retake_results ADD COLUMN `module_id` INT(11) NULL AFTER `user_id`");
        echo "✓ Added module_id to retake_results\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "  ℹ module_id already exists in retake_results\n";
        } else {
            echo "  ⚠ Could not add module_id: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ All schema fixes applied!\n";
    echo "\nFinal database status:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Total tables: " . count($tables) . "\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
