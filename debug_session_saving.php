<?php
/**
 * Debug Script for Session Data Saving
 * This will help identify why focused_time_seconds is 0
 */

header('Content-Type: text/plain');

echo "==============================================\n";
echo "Session Data Saving Debug Report\n";
echo "==============================================\n\n";

// Use centralized database connection
require_once __DIR__ . '/database/db_connection.php';

try {
    $conn = getMysqliConnection();
    echo "✓ Database connection successful\n\n";
    
    // Check 1: Verify table structure
    echo "=== Check 1: Table Structure ===\n";
    $columns = $conn->query("SHOW COLUMNS FROM eye_tracking_sessions");
    echo "Columns in eye_tracking_sessions:\n";
    while ($col = $columns->fetch_assoc()) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";
    
    // Check 2: Recent session data
    echo "=== Check 2: Recent Session Data ===\n";
    $recent = $conn->query("
        SELECT id, user_id, module_id, section_id, 
               total_time_seconds, focused_time_seconds, unfocused_time_seconds,
               session_type, created_at
        FROM eye_tracking_sessions 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    
    echo "Last 5 sessions:\n";
    while ($row = $recent->fetch_assoc()) {
        echo "  ID: {$row['id']}\n";
        echo "    User: {$row['user_id']}, Module: {$row['module_id']}, Section: " . ($row['section_id'] ?? 'NULL') . "\n";
        echo "    Total Time: {$row['total_time_seconds']}s\n";
        echo "    Focused: {$row['focused_time_seconds']}s, Unfocused: {$row['unfocused_time_seconds']}s\n";
        echo "    Type: {$row['session_type']}, Created: {$row['created_at']}\n";
        echo "  ---\n";
    }
    echo "\n";
    
    // Check 3: Check if session_data column exists
    echo "=== Check 3: Session Data Column ===\n";
    $has_session_data = $conn->query("SHOW COLUMNS FROM eye_tracking_sessions LIKE 'session_data'");
    if ($has_session_data && $has_session_data->num_rows > 0) {
        echo "✓ session_data column exists\n";
        
        // Check if it has any data
        $data_check = $conn->query("SELECT COUNT(*) as count FROM eye_tracking_sessions WHERE session_data IS NOT NULL AND session_data != ''");
        $count = $data_check->fetch_assoc()['count'];
        echo "  Rows with session_data: $count\n";
    } else {
        echo "✗ session_data column missing\n";
    }
    echo "\n";
    
    // Check 4: Verify save endpoints exist
    echo "=== Check 4: Save Endpoints ===\n";
    $endpoints = [
        'user/database/save_enhanced_tracking.php',
        'user/database/save_session_data.php',
        'user/database/save_cv_eye_tracking.php'
    ];
    
    foreach ($endpoints as $endpoint) {
        if (file_exists(__DIR__ . '/' . $endpoint)) {
            echo "✓ $endpoint exists\n";
        } else {
            echo "✗ $endpoint NOT FOUND\n";
        }
    }
    echo "\n";
    
    // Check 5: Verify which endpoint handles focused_time
    echo "=== Check 5: Endpoint Analysis ===\n";
    
    // Check save_enhanced_tracking.php
    if (file_exists(__DIR__ . '/user/database/save_enhanced_tracking.php')) {
        $content = file_get_contents(__DIR__ . '/user/database/save_enhanced_tracking.php');
        if (strpos($content, 'focused_time_seconds') !== false) {
            echo "✓ save_enhanced_tracking.php handles focused_time_seconds\n";
        } else {
            echo "⚠ save_enhanced_tracking.php doesn't mention focused_time_seconds\n";
        }
    }
    
    // Check save_session_data.php
    if (file_exists(__DIR__ . '/user/database/save_session_data.php')) {
        $content = file_get_contents(__DIR__ . '/user/database/save_session_data.php');
        if (strpos($content, 'focused_time') !== false) {
            echo "✓ save_session_data.php has focused_time logic\n";
        } else {
            echo "⚠ save_session_data.php doesn't handle focused_time\n";
        }
    }
    echo "\n";
    
    // Recommendations
    echo "==============================================\n";
    echo "Diagnosis & Recommendations\n";
    echo "==============================================\n\n";
    
    // Check the issue
    $zero_focused = $conn->query("SELECT COUNT(*) as count FROM eye_tracking_sessions WHERE total_time_seconds > 0 AND focused_time_seconds = 0");
    $zero_count = $zero_focused->fetch_assoc()['count'];
    
    if ($zero_count > 0) {
        echo "❌ ISSUE FOUND:\n";
        echo "   $zero_count sessions have total_time > 0 but focused_time = 0\n\n";
        
        echo "Possible Causes:\n";
        echo "1. Frontend is calling the wrong endpoint\n";
        echo "2. Frontend is not sending focused_time_seconds data\n";
        echo "3. The endpoint is not populating focused_time_seconds\n";
        echo "4. Eye tracking service is not calculating focus time\n\n";
        
        echo "To Fix:\n";
        echo "1. Check browser console for API calls\n";
        echo "2. Verify which endpoint is being called\n";
        echo "3. Check if focused_time is in the request payload\n";
        echo "4. Verify Python eye tracking service is running\n";
    } else {
        echo "✓ No issues found - focused_time is being populated correctly\n";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
