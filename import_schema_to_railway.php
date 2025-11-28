<?php
/**
 * Import Database Schema to Railway
 * This script imports the elearn_db.sql schema to Railway MySQL
 */

echo "Starting Railway Database Import...\n\n";

// Load database connection
require_once __DIR__ . '/database/db_connection.php';

try {
    $pdo = getPDOConnection();
    echo "✓ Connected to Railway database\n\n";
    
    // Read the SQL file
    $sqlFile = __DIR__ . '/database/elearn_db.sql';
    
    if (!file_exists($sqlFile)) {
        die("✗ Error: SQL file not found at $sqlFile\n");
    }
    
    echo "Reading SQL file...\n";
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        die("✗ Error: Could not read SQL file\n");
    }
    
    $fileSize = number_format(strlen($sql) / 1024, 2);
    echo "✓ SQL file loaded ($fileSize KB)\n\n";
    
    // Disable foreign key checks temporarily
    echo "Disabling foreign key checks...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
    
    echo "Splitting SQL into statements...\n";
    
    // Remove comments and split by semicolon
    $sql = preg_replace('/--.*$/m', '', $sql); // Remove single-line comments
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove multi-line comments
    
    // Split by semicolon, but be careful with semicolons in strings
    $statements = [];
    $current = '';
    $inString = false;
    $stringChar = '';
    
    for ($i = 0; $i < strlen($sql); $i++) {
        $char = $sql[$i];
        
        if (($char === "'" || $char === '"') && ($i === 0 || $sql[$i-1] !== '\\')) {
            if (!$inString) {
                $inString = true;
                $stringChar = $char;
            } elseif ($char === $stringChar) {
                $inString = false;
            }
        }
        
        if ($char === ';' && !$inString) {
            $statement = trim($current);
            if (!empty($statement)) {
                $statements[] = $statement;
            }
            $current = '';
        } else {
            $current .= $char;
        }
    }
    
    // Add the last statement if any
    $statement = trim($current);
    if (!empty($statement)) {
        $statements[] = $statement;
    }
    
    echo "Found " . count($statements) . " SQL statements\n\n";
    echo "Executing statements...\n";
    
    $executed = 0;
    $skipped = 0;
    $errors = 0;
    
    $progressInterval = max(1, floor(count($statements) / 20));
    
    foreach ($statements as $index => $statement) {
        // Skip empty statements and certain mysql-specific comments
        if (empty($statement) || 
            strpos($statement, '/*!') === 0 ||
            strpos($statement, 'SET @') === 0 ||
            strpos($statement, 'START TRANSACTION') === 0 || 
            strpos($statement, 'COMMIT') === 0) {
            $skipped++;
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
            
            // Show progress
            if ($executed % $progressInterval === 0) {
                $percentage = round(($executed / count($statements)) * 100);
                echo "  Progress: $percentage% ($executed statements executed)\n";
            }
        } catch (PDOException $e) {
            // Some errors we can safely ignore (like table/index already exists)
            $ignorableErrors = [
                'already exists',
                'Duplicate entry',
                'Duplicate key name'
            ];
            
            $isIgnorable = false;
            foreach ($ignorableErrors as $ignorable) {
                if (stripos($e->getMessage(), $ignorable) !== false) {
                    $isIgnorable = true;
                    $skipped++;
                    break;
                }
            }
            
            if (!$isIgnorable) {
                $errors++;
                echo "\n⚠ Error in statement " . ($index + 1) . ":\n";
                echo "  " . substr($statement, 0, 100) . "...\n";
                echo "  Error: " . $e->getMessage() . "\n\n";
                
                // Stop if too many errors
                if ($errors > 10) {
                    echo "\n✗ Too many errors. Stopping import.\n";
                    break;
                }
            }
        }
    }
    
    // Re-enable foreign key checks
    echo "\nRe-enabling foreign key checks...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Import Summary:\n";
    echo "  Total statements: " . count($statements) . "\n";
    echo "  Executed successfully: $executed\n";
    echo "  Skipped: $skipped\n";
    echo "  Errors: $errors\n";
    echo str_repeat("=", 60) . "\n\n";
    
    // Verify tables were created
    echo "Verifying tables...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "✓ Total tables in database: " . count($tables) . "\n\n";
    
    if (count($tables) > 0) {
        echo "Tables created:\n";
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            echo "  - $table ($count rows)\n";
        }
    }
    
    echo "\n✓ Import completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
