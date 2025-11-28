<?php
/**
 * Remove duplicate entries from Railway database
 */

echo "Cleaning up duplicate entries in Railway database...\n\n";

require_once __DIR__ . '/user/load_env.php';

try {
    $dsn = "mysql:host=" . getenv('DB_HOST') . ";port=" . getenv('DB_PORT') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8mb4";
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to Railway database\n\n";
    
    $totalRemoved = 0;
    
    // 1. Clean module_parts duplicates
    echo "=== CLEANING MODULE_PARTS ===\n";
    
    // Find duplicates in module_parts
    $stmt = $pdo->query("
        SELECT MIN(id) as first_id, module_id, title, COUNT(*) as count
        FROM module_parts
        GROUP BY module_id, title
        HAVING count > 1
    ");
    
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($duplicates) > 0) {
        echo "Found " . count($duplicates) . " duplicate module part(s)\n";
        
        foreach ($duplicates as $dup) {
            echo "  Duplicate: {$dup['title']} (appears {$dup['count']} times)\n";
            
            // Get all IDs for this duplicate
            $stmt = $pdo->prepare("
                SELECT id FROM module_parts 
                WHERE module_id = ? AND title = ?
                ORDER BY id ASC
            ");
            $stmt->execute([$dup['module_id'], $dup['title']]);
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Keep the first one, delete the rest
            $keepId = $ids[0];
            $deleteIds = array_slice($ids, 1);
            
            foreach ($deleteIds as $deleteId) {
                $pdo->prepare("DELETE FROM module_parts WHERE id = ?")->execute([$deleteId]);
                echo "    Removed duplicate ID: $deleteId (kept ID: $keepId)\n";
                $totalRemoved++;
            }
        }
    } else {
        echo "  No duplicates found in module_parts\n";
    }
    
    echo "\n";
    
    // 2. Clean module_sections duplicates
    echo "=== CLEANING MODULE_SECTIONS ===\n";
    
    $stmt = $pdo->query("
        SELECT MIN(id) as first_id, module_part_id, subtitle, COUNT(*) as count
        FROM module_sections
        GROUP BY module_part_id, subtitle
        HAVING count > 1
    ");
    
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($duplicates) > 0) {
        echo "Found " . count($duplicates) . " duplicate module section(s)\n";
        
        foreach ($duplicates as $dup) {
            echo "  Duplicate: {$dup['subtitle']} (appears {$dup['count']} times)\n";
            
            $stmt = $pdo->prepare("
                SELECT id FROM module_sections 
                WHERE module_part_id = ? AND subtitle = ?
                ORDER BY id ASC
            ");
            $stmt->execute([$dup['module_part_id'], $dup['subtitle']]);
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $keepId = $ids[0];
            $deleteIds = array_slice($ids, 1);
            
            foreach ($deleteIds as $deleteId) {
                $pdo->prepare("DELETE FROM module_sections WHERE id = ?")->execute([$deleteId]);
                echo "    Removed duplicate ID: $deleteId (kept ID: $keepId)\n";
                $totalRemoved++;
            }
        }
    } else {
        echo "  No duplicates found in module_sections\n";
    }
    
    echo "\n";
    
    // 3. Clean modules duplicates
    echo "=== CLEANING MODULES ===\n";
    
    $stmt = $pdo->query("
        SELECT MIN(id) as first_id, title, COUNT(*) as count
        FROM modules
        GROUP BY title
        HAVING count > 1
    ");
    
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($duplicates) > 0) {
        echo "Found " . count($duplicates) . " duplicate module(s)\n";
        
        foreach ($duplicates as $dup) {
            echo "  Duplicate: {$dup['title']} (appears {$dup['count']} times)\n";
            
            $stmt = $pdo->prepare("
                SELECT id FROM modules 
                WHERE title = ?
                ORDER BY id ASC
            ");
            $stmt->execute([$dup['title']]);
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $keepId = $ids[0];
            $deleteIds = array_slice($ids, 1);
            
            foreach ($deleteIds as $deleteId) {
                $pdo->prepare("DELETE FROM modules WHERE id = ?")->execute([$deleteId]);
                echo "    Removed duplicate ID: $deleteId (kept ID: $keepId)\n";
                $totalRemoved++;
            }
        }
    } else {
        echo "  No duplicates found in modules\n";
    }
    
    echo "\n";
    
    // Summary
    echo str_repeat("=", 60) . "\n";
    echo "Cleanup Summary:\n";
    echo "  Total duplicate entries removed: $totalRemoved\n";
    echo str_repeat("=", 60) . "\n\n";
    
    // Verify final state
    echo "Verifying final state...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM module_parts");
    $modulePartsCount = $stmt->fetchColumn();
    echo "  module_parts: $modulePartsCount rows\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM module_sections");
    $moduleSectionsCount = $stmt->fetchColumn();
    echo "  module_sections: $moduleSectionsCount rows\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM modules");
    $modulesCount = $stmt->fetchColumn();
    echo "  modules: $modulesCount rows\n";
    
    echo "\n✅ Cleanup completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
