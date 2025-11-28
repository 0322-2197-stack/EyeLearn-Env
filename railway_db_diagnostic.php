<?php
/**
 * Railway Database Connection Diagnostic
 * This script helps identify why data isn't being saved to Railway database
 */

header('Content-Type: text/plain; charset=utf-8');

echo "==============================================\n";
echo "Railway Database Connection Diagnostic\n";
echo "==============================================\n\n";

// Check 1: Environment Variables
echo "=== Check 1: Environment Variables ===\n";
$env_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_PORT'];
$env_status = [];

foreach ($env_vars as $var) {
    $value = getenv($var);
    if ($value !== false) {
        // Mask password
        $display_value = ($var === 'DB_PASS') ? str_repeat('*', min(strlen($value), 8)) : $value;
        echo "✓ $var = $display_value\n";
        $env_status[$var] = true;
    } else {
        echo "✗ $var = NOT SET\n";
        $env_status[$var] = false;
    }
}

$all_env_set = !in_array(false, $env_status);
echo "\nEnvironment variables: " . ($all_env_set ? "✓ ALL SET" : "✗ SOME MISSING") . "\n\n";

// Check 2: Database Connection
echo "=== Check 2: Database Connection Test ===\n";

// Try centralized connection
try {
    require_once __DIR__ . '/database/db_connection.php';
    echo "✓ db_connection.php loaded successfully\n";
    
    try {
        $pdo = getPDOConnection();
        echo "✓ PDO connection successful\n";
        
        // Test query
        $stmt = $pdo->query("SELECT DATABASE() as current_db, VERSION() as version");
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  Database: {$info['current_db']}\n";
        echo "  MySQL Version: {$info['version']}\n";
        
    } catch (Exception $e) {
        echo "✗ PDO connection failed: " . $e->getMessage() . "\n";
    }
    
    try {
        $mysqli = getMysqliConnection();
        echo "✓ MySQLi connection successful\n";
        echo "  Host: " . $mysqli->host_info . "\n";
        
    } catch (Exception $e) {
        echo "✗ MySQLi connection failed: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Failed to load db_connection.php: " . $e->getMessage() . "\n";
}

echo "\n";

// Check 3: Table Structure
echo "=== Check 3: Table Structure ===\n";
try {
    $pdo = getPDOConnection();
    
    // Check if eye_tracking_sessions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'eye_tracking_sessions'");
    if ($stmt->rowCount() > 0) {
        echo "✓ eye_tracking_sessions table exists\n";
        
        // Check columns
        $columns_stmt = $pdo->query("SHOW COLUMNS FROM eye_tracking_sessions");
        $columns = $columns_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $required_columns = ['id', 'user_id', 'module_id', 'total_time_seconds', 
                            'focused_time_seconds', 'unfocused_time_seconds', 'session_type'];
        
        foreach ($required_columns as $col) {
            if (in_array($col, $columns)) {
                echo "  ✓ Column: $col\n";
            } else {
                echo "  ✗ Missing column: $col\n";
            }
        }
        
        // Count existing records
        $count_stmt = $pdo->query("SELECT COUNT(*) FROM eye_tracking_sessions");
        $count = $count_stmt->fetchColumn();
        echo "  Total records: $count\n";
        
    } else {
        echo "✗ eye_tracking_sessions table DOES NOT EXIST\n";
        echo "  ⚠ You need to run the database migration!\n";
    }
    
} catch (Exception $e) {
    echo "✗ Table check failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Check 4: Write Test
echo "=== Check 4: Database Write Test ===\n";
try {
    $pdo = getPDOConnection();
    
    // Try to insert a test record
    $test_sql = "INSERT INTO eye_tracking_sessions 
                (user_id, module_id, section_id, total_time_seconds, focused_time_seconds, 
                 unfocused_time_seconds, session_type, created_at, last_updated) 
                VALUES (999, 999, 999, 10, 5, 5, 'test', NOW(), NOW())";
    
    $pdo->exec($test_sql);
    echo "✓ Test write successful\n";
    
    // Check if it was written
    $check_stmt = $pdo->query("SELECT * FROM eye_tracking_sessions WHERE user_id = 999 AND module_id = 999");
    $test_record = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($test_record) {
        echo "  ✓ Test record verified in database\n";
        echo "  ID: {$test_record['id']}\n";
        echo "  Focused time: {$test_record['focused_time_seconds']}s\n";
        
        // Clean up test record
        $pdo->exec("DELETE FROM eye_tracking_sessions WHERE user_id = 999 AND module_id = 999");
        echo "  ✓ Test record cleaned up\n";
    } else {
        echo "  ✗ Test record not found after insert\n";
    }
    
} catch (Exception $e) {
    echo "✗ Write test failed: " . $e->getMessage() . "\n";
    echo "  This could mean:\n";
    echo "  - Database permissions issue\n";
    echo "  - Table doesn't exist\n";
    echo "  - Missing columns\n";
}

echo "\n";

// Check 5: Recent Activity
echo "=== Check 5: Recent Database Activity ===\n";
try {
    $pdo = getPDOConnection();
    
    $recent_stmt = $pdo->query("
        SELECT id, user_id, module_id, total_time_seconds, focused_time_seconds, 
               session_type, created_at 
        FROM eye_tracking_sessions 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    
    $recent_records = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($recent_records) > 0) {
        echo "Recent sessions (last 5):\n";
        foreach ($recent_records as $record) {
            echo "  - ID: {$record['id']}, User: {$record['user_id']}, ";
            echo "Module: {$record['module_id']}, ";
            echo "Total: {$record['total_time_seconds']}s, ";
            echo "Focused: {$record['focused_time_seconds']}s, ";
            echo "Type: {$record['session_type']}, ";
            echo "Created: {$record['created_at']}\n";
        }
    } else {
        echo "⚠ No records found in database\n";
        echo "  Possible reasons:\n";
        echo "  - No one has used the app yet\n";
        echo "  - Data isn't being saved (check your app's save endpoint)\n";
        echo "  - Wrong database connection (connected to different DB)\n";
    }
    
} catch (Exception $e) {
    echo "✗ Recent activity check failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Final Diagnosis
echo "==============================================\n";
echo "Diagnosis Summary\n";
echo "==============================================\n\n";

$issues = [];
$warnings = [];

if (!$all_env_set) {
    $issues[] = "Environment variables not properly configured";
}

try {
    $pdo = getPDOConnection();
    
    // Check table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'eye_tracking_sessions'")->rowCount();
    if ($table_check == 0) {
        $issues[] = "eye_tracking_sessions table missing - run migration!";
    }
    
    // Check for records
    $record_count = $pdo->query("SELECT COUNT(*) FROM eye_tracking_sessions")->fetchColumn();
    if ($record_count == 0) {
        $warnings[] = "No data in database yet - try using the app";
    }
    
} catch (Exception $e) {
    $issues[] = "Cannot query database: " . $e->getMessage();
}

if (count($issues) > 0) {
    echo "❌ ISSUES FOUND:\n";
    foreach ($issues as $issue) {
        echo "  - $issue\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠️  WARNINGS:\n";
    foreach ($warnings as $warning) {
        echo "  - $warning\n";
    }
    echo "\n";
}

if (count($issues) == 0 && count($warnings) == 0) {
    echo "✅ Everything looks good!\n";
    echo "Database connection is working and table structure is correct.\n\n";
}

echo "==============================================\n";
echo "Next Steps:\n";
echo "==============================================\n\n";

if (!$all_env_set) {
    echo "1. Set Railway environment variables:\n";
    echo "   - Go to Railway project settings\n";
    echo "   - Set DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT\n";
    echo "   - Use Railway's MySQL connection details\n\n";
}

try {
    $pdo = getPDOConnection();
    $table_check = $pdo->query("SHOW TABLES LIKE 'eye_tracking_sessions'")->rowCount();
    if ($table_check == 0) {
        echo "2. Run database migration:\n";
        echo "   - Open Railway MySQL service\n";
        echo "   - Go to Data > Query tab\n";
        echo "   - Run: database/elearn_db.sql\n";
        echo "   - Then run: database/migrations/001_consolidate_analytics_columns.sql\n\n";
    }
} catch (Exception $e) {
    // Can't check, skip
}

echo "3. Test the application:\n";
echo "   - Open a module in your deployed app\n";
echo "   - Use it for 1-2 minutes\n";
echo "   - Run this diagnostic again\n";
echo "   - Check if new records appear\n\n";

echo "4. Check application logs:\n";
echo "   - Look for PHP errors in Railway logs\n";
echo "   - Check for database connection errors\n";
echo "   - Verify save_session_data.php is being called\n";
?>
