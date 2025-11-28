<?php
/**
 * Verification script for database connection centralization
 * Checks all production files to ensure they use centralized connection
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Centralization Verification</h1>\n";
echo "<pre>\n";

$issues = [];
$success = [];

// Check production directories
$production_dirs = [
    'user' => 'User modules',
    'admin' => 'Admin modules',
    'config.php' => 'Configuration'
];

foreach ($production_dirs as $dir => $label) {
    echo "\n=== Checking $label ===\n";
    
    $path = is_file($dir) ? $dir : __DIR__ . '/' . $dir;
    $files = [];
    
    if (is_file($path)) {
        $files = [$path];
    } else {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }
    
    foreach ($files as $file) {
        $relative_path = str_replace(__DIR__ . '/', '', $file);
        
        // Skip test, debug, backup, and template files
        if (strpos($relative_path, 'test_') !== false || 
            strpos($relative_path, 'debug_') !== false ||
            strpos($relative_path, 'BCK') !== false ||
            strpos($relative_path, 'backup') !== false ||
            strpos($relative_path, 'template') !== false ||
            strpos($relative_path, 'comprehensive_debug') !== false) {
            continue;
        }
        
        $content = file_get_contents($file);
        
        // Check if file uses centralized connection
        $uses_centralized = (
            strpos($content, 'db_connection.php') !== false ||
            strpos($content, 'getMysqliConnection') !== false ||
            strpos($content, 'getPDOConnection') !== false
        );
        
        // Check if file creates direct connection
        $has_direct_connection = (
            preg_match('/new\s+mysqli\s*\(["\']localhost["\']/', $content) ||
            preg_match('/new\s+PDO\s*\(["\']mysql:host=localhost/', $content)
        );
        
        if ($has_direct_connection && !$uses_centralized) {
            $issues[] = [
                'file' => $relative_path,
                'issue' => 'Creates direct database connection instead of using centralized connection'
            ];
            echo "✗ $relative_path - Has direct connection\n";
        } elseif ($uses_centralized) {
            $success[] = $relative_path;
            echo "✓ $relative_path - Uses centralized connection\n";
        }
    }
}

// Check key files specifically
echo "\n=== Key Files Verification ===\n";
$key_files = [
    'config.php',
    'config_environment.php',
    'database/db_connection.php'
];

foreach ($key_files as $file) {
    $file_path = __DIR__ . '/' . $file;
    if (file_exists($file_path)) {
        echo "✓ $file exists\n";
    } else {
        $issues[] = [
            'file' => $file,
            'issue' => 'Key file missing'
        ];
        echo "✗ $file missing\n";
    }
}

// Summary
echo "\n========================================\n";
echo "VERIFICATION SUMMARY\n";
echo "========================================\n";
echo "Files using centralized connection: " . count($success) . "\n";
echo "Files with issues: " . count($issues) . "\n\n";

if (empty($issues)) {
    echo "✓ SUCCESS: All production files are using centralized database connection!\n";
} else {
    echo "✗ ISSUES FOUND:\n";
    foreach ($issues as $issue) {
        echo "  - {$issue['file']}: {$issue['issue']}\n";
    }
}

// Check for import patterns
echo "\n=== Import Pattern Analysis ===\n";
$import_patterns = [
    'require_once.*db_connection' => 0,
    'include.*db_connection' => 0,
    'getMysqliConnection' => 0,
    'getPDOConnection' => 0
];

$all_files = array_merge($success, array_column($issues, 'file'));
foreach ($all_files as $file) {
    $file_path = __DIR__ . '/' . $file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        foreach ($import_patterns as $pattern => $count) {
            if (preg_match("/$pattern/", $content)) {
                $import_patterns[$pattern]++;
            }
        }
    }
}

echo "Import pattern usage:\n";
foreach ($import_patterns as $pattern => $count) {
    echo "  $pattern: $count files\n";
}

echo "</pre>\n";

