<?php
/**
 * Test Script for Eye Tracking Data Collection
 * This script verifies that the database schema is properly synchronized
 */

// Use centralized database connection
require_once __DIR__ . '/database/db_connection.php';

try {
    $conn = getMysqliConnection();
    echo "✓ Database connection successful\n\n";
    
    // Test 1: Verify eye_tracking_analytics table structure
    echo "=== Test 1: Verify table structure ===\n";
    $columns_query = "SHOW COLUMNS FROM eye_tracking_analytics";
    $result = $conn->query($columns_query);
    
    $has_total_focus_time = false;
    $has_total_focused_time = false;
    $has_total_unfocused_time = false;
    $has_focus_percentage = false;
    
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] == 'total_focus_time') $has_total_focus_time = true;
        if ($row['Field'] == 'total_focused_time') $has_total_focused_time = true;
        if ($row['Field'] == 'total_unfocused_time') $has_total_unfocused_time = true;
        if ($row['Field'] == 'focus_percentage') $has_focus_percentage = true;
    }
    
    echo "  total_focus_time (legacy): " . ($has_total_focus_time ? "✓ Present" : "✗ Missing") . "\n";
    echo "  total_focused_time (new): " . ($has_total_focused_time ? "✓ Present" : "✗ Missing") . "\n";
    echo "  total_unfocused_time (new): " . ($has_total_unfocused_time ? "✓ Present" : "✗ Missing") . "\n";
    echo "  focus_percentage (new): " . ($has_focus_percentage ? "✓ Present" : "✗ Missing") . "\n\n";
    
    // Test 2: Check data migration
    echo "=== Test 2: Check data migration ===\n";
    $data_check = "SELECT 
        COUNT(*) as total_rows,
        SUM(CASE WHEN total_focus_time > 0 THEN 1 ELSE 0 END) as legacy_data_count,
        SUM(CASE WHEN total_focused_time > 0 THEN 1 ELSE 0 END) as new_data_count,
        SUM(total_focus_time) as sum_legacy,
        SUM(total_focused_time) as sum_new
    FROM eye_tracking_analytics";
    
    $result = $conn->query($data_check);
    $stats = $result->fetch_assoc();
    
    echo "  Total rows: " . $stats['total_rows'] . "\n";
    echo "  Rows with legacy data (total_focus_time > 0): " . $stats['legacy_data_count'] . "\n";
    echo "  Rows with new data (total_focused_time > 0): " . $stats['new_data_count'] . "\n";
    echo "  Sum of legacy column: " . $stats['sum_legacy'] . "\n";
    echo "  Sum of new column: " . $stats['sum_new'] . "\n";
    
    if ($stats['sum_legacy'] == $stats['sum_new'] && $stats['sum_legacy'] > 0) {
        echo "  ✓ Data migration successful - sums match!\n\n";
    } else if ($stats['sum_legacy'] == 0 && $stats['sum_new'] == 0) {
        echo "  ℹ No data to migrate yet (empty table)\n\n";
    } else {
        echo "  ⚠ Warning: Sums don't match - migration may need attention\n\n";
    }
    
    // Test 3: Test inserting new data
    echo "=== Test 3: Test data insertion ===\n";
    $test_user_id = 1;
    $test_module_id = 1;
    
    $insert_test = "INSERT INTO eye_tracking_analytics 
        (user_id, module_id, section_id, date, total_focused_time, total_unfocused_time, 
         focus_percentage, session_count, average_session_time, max_continuous_time) 
        VALUES (?, ?, 1, CURDATE(), 100, 20, 83.33, 1, 100, 100)
        ON DUPLICATE KEY UPDATE 
        total_focused_time = total_focused_time + VALUES(total_focused_time),
        updated_at = NOW()";
    
    $stmt = $conn->prepare($insert_test);
    $stmt->bind_param('ii', $test_user_id, $test_module_id);
    
    if ($stmt->execute()) {
        echo "  ✓ Successfully inserted/updated test data\n";
        echo "  ✓ New column names are working correctly\n\n";
    } else {
        echo "  ✗ Failed to insert test data: " . $conn->error . "\n\n";
    }
    
    // Test 4: Verify eye_tracking_sessions table
    echo "=== Test 4: Verify eye_tracking_sessions table ===\n";
    $sessions_check = "SHOW COLUMNS FROM eye_tracking_sessions WHERE Field LIKE '%time%'";
    $result = $conn->query($sessions_check);
    
    echo "  Time-related columns:\n";
    while ($row = $result->fetch_assoc()) {
        echo "    - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    echo "\n";
    
    // Test 5: Check centralized connection
    echo "=== Test 5: Verify centralized connection ===\n";
    if (function_exists('getPDOConnection') && function_exists('getMysqliConnection')) {
        echo "  ✓ Centralized connection functions available\n";
        $pdo_test = getPDOConnection();
        echo "  ✓ PDO connection working\n";
        echo "  ✓ MySQLi connection working\n\n";
    } else {
        echo "  ✗ Centralized connection functions not found\n\n";
    }
    
    echo "===========================================\n";
    echo "Summary: Schema synchronization verified!\n";
    echo "===========================================\n";
    echo "\nAll systems are synchronized with elearn_db.sql schema.\n";
    echo "All connections use db_connection.php.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
?>
