<?php
/**
 * Check module_parts data in Railway ONLY
 */

echo "Checking module_parts and module_sections in Railway...\n\n";

require_once __DIR__ . '/user/load_env.php';

try {
    $dsn = "mysql:host=" . getenv('DB_HOST') . ";port=" . getenv('DB_PORT') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8mb4";
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to Railway database\n\n";
    
    // Check modules
    echo "=== MODULES ===\n";
    $stmt = $pdo->query("SELECT id, title FROM modules ORDER BY id");
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($modules) . " modules:\n";
    foreach ($modules as $module) {
        echo "  - Module ID {$module['id']}: {$module['title']}\n";
    }
    echo "\n";
    
    // Check module_parts
    echo "=== MODULE_PARTS ===\n";
    $stmt = $pdo->query("SELECT * FROM module_parts ORDER BY module_id, id");
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($parts) > 0) {
        echo "Found " . count($parts) . " module parts:\n";
        foreach ($parts as $part) {
            echo "  - Part ID {$part['id']}: {$part['title']} (Module ID: {$part['module_id']})\n";
        }
    } else {
        echo "⚠ WARNING: No module parts found!\n";
        echo "   Dashboard won't show module sections without module_parts data.\n";
    }
    echo "\n";
    
    // Check module_sections
    echo "=== MODULE_SECTIONS ===\n";
    $stmt = $pdo->query("SELECT * FROM module_sections ORDER BY module_part_id, id LIMIT 10");
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($sections) > 0) {
        echo "Found module sections (showing first 10):\n";
        foreach ($sections as $section) {
            $subtitle = substr($section['subtitle'], 0, 50);
            echo "  - Section ID {$section['id']}: {$subtitle}... (Part ID: {$section['module_part_id']})\n";
        }
    } else {
        echo "⚠ WARNING: No module sections found!\n";
        echo "   Module parts won't have any content sections.\n";
    }
    echo "\n";
    
    echo "✅ Diagnostic complete!\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
