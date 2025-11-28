<?php
/**
 * Import elearn_db (4).sql to Railway MySQL
 */

echo "Starting Railway Database Import from elearn_db (4).sql...\n\n";

require_once __DIR__ . '/user/load_env.php';

try {
    $dsn = "mysql:host=" . getenv('DB_HOST') . ";port=" . getenv('DB_PORT') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8mb4";
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to Railway MySQL\n\n";
    
    // Read SQL file
    $sqlFile = __DIR__ . '/database/elearn_db (4).sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    $fileSize = filesize($sqlFile);
    echo "Reading SQL file: " . basename($sqlFile) . " (" . round($fileSize / 1024, 2) . " KB)\n\n";
    
    // Disable foreign key checks
    echo "Disabling foreign key checks...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Split SQL into statements
    echo "Splitting SQL into statements...\n";
    $statements = [];
    $current = '';
    $inString = false;
    $stringChar = '';
    
    for ($i = 0; $i < strlen($sql); $i++) {
        $char = $sql[$i];
        $prev = $i > 0 ? $sql[$i-1] : '';
        
        // Handle string delimiters
        if (($char == "'" || $char == '"') && $prev != '\\') {
            if (!$inString) {
                $inString = true;
                $stringChar = $char;
            } elseif ($char == $stringChar) {
                $inString = false;
            }
        }
        
        $current .= $char;
        
        // Split on semicolon outside of strings
        if ($char == ';' && !$inString) {
            $stmt = trim($current);
            if (!empty($stmt) && 
                !preg_match('/^--|^\/\*|^SET|^START TRANSACTION|^COMMIT|^\/\*!/', $stmt)) {
                $statements[] = $stmt;
            }
            $current = '';
        }
    }
    
    echo "Found " . count($statements) . " statements\n\n";
    
    // Execute statements
    $executed = 0;
    $skipped = 0;
    $errors = 0;
    $maxErrors = 20;
    
    foreach ($statements as $i => $statement) {
        try {
            // Skip comments and empty statements
            if (empty($statement) || 
                preg_match('/^--|^\/\*|^$/', $statement)) {
                $skipped++;
                continue;
            }
            
            $pdo->exec($statement);
            $executed++;
            
            // Show progress every 50 statements
            if ($executed % 50 == 0) {
                echo "Processed $executed statements...\n";
            }
            
        } catch (PDOException $e) {
            $errors++;
            
            // Only show first few errors to avoid spam
            if ($errors <= 10) {
                $errorMsg = $e->getMessage();
                $shortStmt = strlen($statement) > 100 ? substr($statement, 0, 100) . '...' : $statement;
                echo "⚠ Error in statement " . ($i + 1) . ":\n";
                echo "  " . $errorMsg . "\n";
                echo "  Statement: " . $shortStmt . "\n";
            }
            
            if ($errors > $maxErrors) {
                echo "✗ Too many errors. Stopping import.\n";
                break;
            }
            
            continue;
        }
    }
    
    // Re-enable foreign key checks
    echo "\nRe-enabling foreign key checks...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Summary
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Import Summary:\n";
    echo "  Total statements: " . count($statements) . "\n";
    echo "  Executed successfully: $executed\n";
    echo "  Skipped: $skipped\n";
    echo "  Errors: $errors\n";
    echo str_repeat("=", 60) . "\n\n";
    
    // Verify tables
    echo "Verifying tables...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "✓ Total tables in database: " . count($tables) . "\n";
    echo "Tables created:\n";
    
    // Get row counts for each table
    foreach ($tables as $table) {
        try {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
            $count = $countStmt->fetchColumn();
            echo "  - $table ($count rows)\n";
        } catch (PDOException $e) {
            echo "  - $table (error getting count)\n";
        }
    }
    
    echo "\n✓ Import completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
