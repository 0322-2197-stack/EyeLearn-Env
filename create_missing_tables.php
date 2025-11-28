<?php
/**
 * Create all missing tables on Railway
 */

echo "Checking and creating missing tables on Railway...\n\n";

// Load environment variables
require_once __DIR__ . '/user/load_env.php';

// Create PDO connection directly
try {
    $dsn = "mysql:host=" . getenv('DB_HOST') . ";port=" . getenv('DB_PORT') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8mb4";
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to Railway database\n\n";
    
    // Get existing tables
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Existing tables (" . count($existingTables) . "):\n";
    foreach ($existingTables as $table) {
        echo "  - $table\n";
    }
    echo "\n";
    
    // Tables we need to create
    $tablesToCreate = [];
    
    // Check for quiz_results table
    if (!in_array('quiz_results', $existingTables)) {
        echo "❌ Missing: quiz_results\n";
        $tablesToCreate[] = [
            'name' => 'quiz_results',
            'sql' => "CREATE TABLE `quiz_results` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `module_id` int(11) NOT NULL,
              `quiz_id` int(11) NOT NULL,
              `score` int(11) NOT NULL,
              `completion_date` timestamp NOT NULL DEFAULT current_timestamp(),
              `percentage` decimal(5,2) DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
        ];
    }
    
    // Check for retake_results table
    if (!in_array('retake_results', $existingTables)) {
        echo "❌ Missing: retake_results\n";
        $tablesToCreate[] = [
            'name' => 'retake_results',
            'sql' => "CREATE TABLE `retake_results` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `retake_id` int(11) NOT NULL,
              `user_id` int(11) NOT NULL,
              `quiz_id` int(11) NOT NULL,
              `score` int(11) NOT NULL,
              `completion_date` timestamp NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
        ];
    }
    
    // Create missing tables
    if (count($tablesToCreate) > 0) {
        echo "\nCreating missing tables...\n";
        foreach ($tablesToCreate as $table) {
            echo "  Creating {$table['name']}... ";
            try {
                $pdo->exec($table['sql']);
                echo "✓\n";
            } catch (PDOException $e) {
                echo "✗ Error: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "✓ All required tables exist\n";
    }
    
    // Verify final state
    echo "\nFinal verification...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $finalTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Total tables: " . count($finalTables) . "\n\n";
    
    foreach ($finalTables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        $count = $stmt->fetchColumn();
        $icon = $count > 0 ? "✓" : "○";
        echo "  $icon $table ($count rows)\n";
    }
    
    echo "\n✅ Database configuration complete!\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
