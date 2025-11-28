<?php
/**
 * Import modules table data to Railway
 */

echo "Importing modules table to Railway...\n\n";

require_once __DIR__ . '/user/load_env.php';

try {
    $dsn = "mysql:host=" . getenv('DB_HOST') . ";port=" . getenv('DB_PORT') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8mb4";
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to Railway database\n\n";
    
    // First, clear existing modules
    echo "Clearing existing modules...\n";
    $pdo->exec("DELETE FROM modules");
    echo "✓ Existing modules cleared\n\n";
    
    // Insert module from elearn_db.sql
    echo "Inserting module data...\n";
    $insertSQL = "INSERT INTO `modules` (`id`, `title`, `description`, `image_path`, `created_at`, `updated_at`, `status`) VALUES
    (22, 'MODULE 4: Introduction to IT Computing', 'This lesson will discuss the basic concepts and principles of internet security as well as different internet threats.  This lesson will also provide activities and exercises that will practice the students\' competence in identifying internet threats to avoid being victims of cybercrimes.', '/capstone/modulephotoshow/module_1763906870_e9c1a0f7f12693f7.jpg', '2025-11-23 01:47:24', '2025-11-23 14:12:54', 'published')";
    
    $pdo->exec($insertSQL);
    echo "✓ Module inserted successfully\n\n";
    
    // Verify
    echo "Verifying modules table...\n";
    $stmt = $pdo->query("SELECT id, title, status FROM modules");
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total modules: " . count($modules) . "\n\n";
    foreach ($modules as $module) {
        echo "  ✓ Module ID {$module['id']}: {$module['title']} [{$module['status']}]\n";
    }
    
    echo "\n✅ Modules table imported successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
