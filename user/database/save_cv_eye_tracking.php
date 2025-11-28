<?php
session_start();
header('Content-Type: application/json');

// Allow CORS for the Python service
header('Access-Control-Allow-Origin: http://127.0.0.1:5000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// For now, we'll use a test user ID since session may not be available from Python service
// In production, you'd want to implement proper authentication
$user_id = 1; // Default test user

// Check if user is authenticated via session (if available)
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
try {
    $conn = getMysqliConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Get POST data (JSON from Python service)
$input = json_decode(file_get_contents('php://input'), true);
$module_id = intval($input['module_id'] ?? 0);
$section_id = intval($input['section_id'] ?? 0);
$time_spent = intval($input['time_spent'] ?? 0); // Time in seconds
$session_type = $input['session_type'] ?? 'viewing'; // Computer vision tracking (using 'viewing' for Railway compatibility)

if ($module_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid module ID']);
    exit();
}

if ($time_spent <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid time spent']);
    exit();
}

try {
    // Check if record exists for today
    $check_query = "SELECT id, total_time_seconds FROM eye_tracking_sessions 
                   WHERE user_id = ? AND module_id = ? AND section_id = ? 
                   AND session_type = 'viewing'
                   AND DATE(created_at) = CURDATE()
                   ORDER BY created_at DESC LIMIT 1";
    
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("iii", $user_id, $module_id, $section_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $row = $result->fetch_assoc();
        $new_total_time = $row['total_time_seconds'] + $time_spent;
        
        $update_query = "UPDATE eye_tracking_sessions 
                        SET total_time_seconds = ?, last_updated = NOW() 
                        WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $new_total_time, $row['id']);
        $update_stmt->execute();
        
        echo json_encode([
            'success' => true, 
            'total_time' => $new_total_time,
            'time_added' => $time_spent,
            'tracking_type' => 'cv_tracking'
        ]);
    } else {
        // Create new record
        $insert_query = "INSERT INTO eye_tracking_sessions 
                        (user_id, module_id, section_id, total_time_seconds, session_type, created_at, last_updated) 
                        VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iiiis", $user_id, $module_id, $section_id, $time_spent, $session_type);
        $insert_stmt->execute();
        
        echo json_encode([
            'success' => true, 
            'total_time' => $time_spent,
            'time_added' => $time_spent,
            'tracking_type' => 'cv_tracking',
            'new_session' => true
        ]);
    }
    
    // Update daily analytics with NEW column names
    updateDailyAnalytics($conn, $user_id, $module_id, $section_id, $time_spent);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

function updateDailyAnalytics($conn, $user_id, $module_id, $section_id, $time_spent) {
    try {
        // Update or insert analytics data using NEW column names
        $check_analytics = "SELECT id, total_focused_time, session_count FROM eye_tracking_analytics 
                           WHERE user_id = ? AND module_id = ? AND section_id = ? AND date = CURDATE()";
        
        $analytics_stmt = $conn->prepare($check_analytics);
        $analytics_stmt->bind_param("iii", $user_id, $module_id, $section_id);
        $analytics_stmt->execute();
        $analytics_result = $analytics_stmt->get_result();
        
        if ($row = $analytics_result->fetch_assoc()) {
            // Update existing record using NEW column name
            $new_total_time = $row['total_focused_time'] + $time_spent;
            $new_session_count = $row['session_count'] + 1;
            $new_avg_time = floor($new_total_time / $new_session_count);
            
            $update_analytics = "UPDATE eye_tracking_analytics 
                               SET total_focused_time = ?, session_count = ?, average_session_time = ?, updated_at = NOW()
                               WHERE id = ?";
            
            $upd_stmt = $conn->prepare($update_analytics);
            $upd_stmt->bind_param("iiii", $new_total_time, $new_session_count, $new_avg_time, $row['id']);
            $upd_stmt->execute();
        } else {
            // Insert new analytics record using NEW column name
            $insert_analytics = "INSERT INTO eye_tracking_analytics 
                               (user_id, module_id, section_id, date, total_focused_time, session_count, average_session_time, created_at, updated_at)
                               VALUES (?, ?, ?, CURDATE(), ?, 1, ?, NOW(), NOW())";
            
            $ins_stmt = $conn->prepare($insert_analytics);
            $ins_stmt->bind_param("iiiii", $user_id, $module_id, $section_id, $time_spent, $time_spent);
            $ins_stmt->execute();
        }
    } catch (Exception $e) {
        // Log error but don't fail the main request
        error_log("Analytics update failed: " . $e->getMessage());
    }
}

$conn->close();
?>
