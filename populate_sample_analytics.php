<?php
/**
 * Script to populate sample eye tracking data for testing
 * Updated to use centralized database connection
 */

// Use centralized database connection
require_once __DIR__ . '/database/db_connection.php';

try {
    $conn = getMysqliConnection();
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

// Sample user ID (change this to match your test user)
$user_id = 1; // Adjust this to your actual user ID

// Sample module IDs (assumes you have modules in your database)
$module_ids = [1, 2, 3]; // Adjust these to match your actual module IDs

echo "Populating sample eye tracking data...\n";

// Generate sample sessions for the last 7 days
for ($days_ago = 6; $days_ago >= 0; $days_ago--) {
    $date = date('Y-m-d', strtotime("-$days_ago days"));
    
    foreach ($module_ids as $module_id) {
        // Create 1-3 sessions per module per day (randomly)
        $sessions_count = rand(1, 3);
        
        for ($session = 0; $session < $sessions_count; $session++) {
            // Random session duration between 300-3600 seconds (5-60 minutes)
            $session_duration = rand(300, 3600);
            
            // Insert session data
            $session_sql = "INSERT INTO eye_tracking_sessions (user_id, module_id, section_id, total_time_seconds, session_type, created_at, last_updated) 
                           VALUES (?, ?, 1, ?, 'viewing', ?, ?)";
            
            $session_time = $date . ' ' . sprintf('%02d:%02d:%02d', rand(8, 20), rand(0, 59), rand(0, 59));
            
            $stmt = $conn->prepare($session_sql);
            $stmt->bind_param('iiiss', $user_id, $module_id, $session_duration, $session_time, $session_time);
            $stmt->execute();
        }
        
        // Calculate daily analytics using NEW column names
        $total_focused_time = rand(900, 3000); // 15-50 minutes of focus time
        $total_unfocused_time = rand(100, 500); // 2-8 minutes unfocused
        $focus_percentage = round(($total_focused_time / ($total_focused_time + $total_unfocused_time)) * 100, 2);
        $session_count = $sessions_count;
        $avg_session_time = intval($total_focused_time / $session_count);
        $max_continuous = rand(600, 1800); // 10-30 minutes max continuous
        
        // Insert analytics data with NEW column names
        $analytics_sql = "INSERT INTO eye_tracking_analytics 
                         (user_id, module_id, section_id, date, total_focused_time, total_unfocused_time, 
                          focus_percentage, session_count, average_session_time, max_continuous_time) 
                         VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?) 
                         ON DUPLICATE KEY UPDATE 
                         total_focused_time = VALUES(total_focused_time),
                         total_unfocused_time = VALUES(total_unfocused_time),
                         focus_percentage = VALUES(focus_percentage),
                         session_count = VALUES(session_count),
                         average_session_time = VALUES(average_session_time),
                         max_continuous_time = VALUES(max_continuous_time)";
        
        $stmt = $conn->prepare($analytics_sql);
        $stmt->bind_param('iisiidiii', $user_id, $module_id, $date, $total_focused_time, 
                         $total_unfocused_time, $focus_percentage, $session_count, 
                         $avg_session_time, $max_continuous);
        $stmt->execute();
    }
}

echo "Sample data populated successfully!\n";
echo "Data includes:\n";
echo "- Sessions for user ID: $user_id\n";
echo "- Modules: " . implode(', ', $module_ids) . "\n";
echo "- Date range: Last 7 days\n";
echo "- Random session durations and focus times\n";

// Show some stats
$stats_sql = "SELECT COUNT(*) as session_count, SUM(total_time_seconds) as total_time FROM eye_tracking_sessions WHERE user_id = ?";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();

echo "\nGenerated Statistics:\n";
echo "- Total sessions: " . $stats['session_count'] . "\n";
echo "- Total study time: " . round($stats['total_time'] / 3600, 1) . " hours\n";

$conn->close();
?>
