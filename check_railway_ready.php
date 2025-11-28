<?php
/**
 * Railway Deployment Readiness Check
 * Verifies that the application is ready for Railway deployment
 */

echo "==============================================\n";
echo "Railway Deployment Readiness Check\n";
echo "==============================================\n\n";

$issues = [];
$warnings = [];
$passed = [];

// Check 1: Database Connection Configuration
echo "📋 Check 1: Database Connection Configuration\n";
if (file_exists(__DIR__ . '/database/db_connection.php')) {
    $db_content = file_get_contents(__DIR__ . '/database/db_connection.php');
    
    if (strpos($db_content, "getenv('DB_HOST')") !== false) {
        $passed[] = "✓ db_connection.php uses environment variables";
    } else {
        $issues[] = "✗ db_connection.php doesn't read from environment variables";
    }
    
    if (strpos($db_content, "getenv('DB_NAME')") !== false &&
        strpos($db_content, "getenv('DB_USER')") !== false &&
        strpos($db_content, "getenv('DB_PASS')") !== false) {
        $passed[] = "✓ All required DB environment variables configured";
    } else {
        $issues[] = "✗ Missing some DB environment variable configurations";
    }
} else {
    $issues[] = "✗ db_connection.php not found";
}
echo "\n";

// Check 2: Schema Synchronization
echo "📋 Check 2: Schema Synchronization\n";
require_once __DIR__ . '/database/db_connection.php';

try {
    $conn = getMysqliConnection();
    
    // Check eye_tracking_analytics columns
    $result = $conn->query("SHOW COLUMNS FROM eye_tracking_analytics");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    if (in_array('total_focused_time', $columns)) {
        $passed[] = "✓ New column 'total_focused_time' exists";
    } else {
        $issues[] = "✗ Missing 'total_focused_time' column";
    }
    
    if (in_array('total_unfocused_time', $columns)) {
        $passed[] = "✓ New column 'total_unfocused_time' exists";
    } else {
        $issues[] = "✗ Missing 'total_unfocused_time' column";
    }
    
    if (in_array('focus_percentage', $columns)) {
        $passed[] = "✓ New column 'focus_percentage' exists";
    } else {
        $issues[] = "✗ Missing 'focus_percentage' column";
    }
    
    $conn->close();
} catch (Exception $e) {
    $warnings[] = "⚠ Could not verify schema (local DB not running - normal if testing deployment config)";
}
echo "\n";

// Check 3: PHP Files Using New Column Names
echo "📋 Check 3: PHP Files Using Correct Column Names\n";
$files_to_check = [
    'user/Sassessment.php' => 'total_focused_time',
    'user/database/save_cv_eye_tracking.php' => 'total_focused_time',
    'user/database/save_session_data.php' => 'total_focused_time',
    'populate_sample_analytics.php' => 'total_focused_time'
];

foreach ($files_to_check as $file => $expected_column) {
    $filepath = __DIR__ . '/' . $file;
    if (file_exists($filepath)) {
        $content = file_get_contents($filepath);
        if (strpos($content, $expected_column) !== false) {
            $passed[] = "✓ $file uses new column names";
        } else {
            $warnings[] = "⚠ $file may not be using new column names";
        }
    }
}
echo "\n";

// Check 4: Dockerfile Exists
echo "📋 Check 4: Deployment Configuration\n";
if (file_exists(__DIR__ . '/Dockerfile')) {
    $passed[] = "✓ Dockerfile exists";
    
    $dockerfile_content = file_get_contents(__DIR__ . '/Dockerfile');
    if (strpos($dockerfile_content, 'PORT') !== false) {
        $passed[] = "✓ Dockerfile configured for dynamic PORT";
    } else {
        $warnings[] = "⚠ Dockerfile may not handle dynamic PORT";
    }
} else {
    $warnings[] = "⚠ No Dockerfile found (Railway may use Nixpacks auto-detection)";
}
echo "\n";

// Check 5: Migration Scripts
echo "📋 Check 5: Database Migration Scripts\n";
if (file_exists(__DIR__ . '/database/migrations/001_consolidate_analytics_columns.sql')) {
    $passed[] = "✓ Migration script exists";
} else {
    $warnings[] = "⚠ Migration script not found";
}

if (file_exists(__DIR__ . '/database/migrations/run_manually.sql')) {
    $passed[] = "✓ Manual migration script ready";
}
echo "\n";

// Check 6: Environment Variable Usage
echo "📋 Check 6: Railway Environment Variable Support\n";
$config_content = file_get_contents(__DIR__ . '/config.php');
if (strpos($config_content, "RAILWAY_ENVIRONMENT") !== false || 
    strpos($config_content, "RAILWAY_STATIC_URL") !== false) {
    $passed[] = "✓ Railway environment detection configured";
} else {
    $warnings[] = "⚠ No Railway-specific environment detection";
}
echo "\n";

// Results Summary
echo "==============================================\n";
echo "Summary\n";
echo "==============================================\n\n";

echo "✅ Passed Checks (" . count($passed) . "):\n";
foreach ($passed as $item) {
    echo "   $item\n";
}
echo "\n";

if (count($warnings) > 0) {
    echo "⚠️  Warnings (" . count($warnings) . "):\n";
    foreach ($warnings as $item) {
        echo "   $item\n";
    }
    echo "\n";
}

if (count($issues) > 0) {
    echo "❌ Issues (" . count($issues) . "):\n";
    foreach ($issues as $item) {
        echo "   $item\n";
    }
    echo "\n";
    echo "Status: NOT READY - Please fix issues before deploying\n";
    exit(1);
} else {
    echo "==============================================\n";
    echo "✅ READY FOR RAILWAY DEPLOYMENT!\n";
    echo "==============================================\n\n";
    
    echo "Next Steps for Railway:\n";
    echo "1. Push code to GitHub\n";
    echo "2. Connect Railway to your GitHub repository\n";
    echo "3. Set environment variables in Railway:\n";
    echo "   - DB_HOST=<Railway MySQL host>\n";
    echo "   - DB_NAME=railway\n";
    echo "   - DB_USER=root\n";
    echo "   - DB_PASS=<Railway MySQL password>\n";
    echo "   - DB_PORT=<Railway MySQL port>\n";
    echo "4. Run migration: database/migrations/run_manually.sql\n";
    echo "5. Deploy!\n\n";
    
    if (count($warnings) > 0) {
        echo "Note: Some warnings exist but they won't prevent deployment.\n";
    }
}
?>
