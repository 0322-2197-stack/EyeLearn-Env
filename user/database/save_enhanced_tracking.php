<?php
/**
 * Eye Tracking Data Save Endpoint
 * 
 * This is the single HTTP entry point for eye-tracking metrics from the Python service.
 * The Python service POSTs tracking data to this endpoint, which then uses the
 * centralized database connection (db_connection.php) to persist the data.
 * 
 * Railway/PaaS Deployment:
 * - Ensure TRACKING_SAVE_URL in Python service points to this endpoint
 * - This endpoint must be reachable over HTTP from the Python service
 * - Database credentials are configured via environment variables in db_connection.php
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';

try {
    $conn = getPDOConnection();
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit();
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON data'
    ]);
    exit();
}

// Validate required fields
$required_fields = ['user_id', 'module_id', 'focused_time', 'unfocused_time', 'total_time', 'focus_percentage'];
foreach ($required_fields as $field) {
    if (!isset($data[$field])) {
        echo json_encode([
            'success' => false,
            'error' => "Missing required field: $field"
        ]);
        exit();
    }
}

try {
    // Save to existing eye_tracking_sessions table (compatible with dashboards)
    $insert_sql = "
        INSERT INTO eye_tracking_sessions (
            user_id, 
            module_id, 
            section_id, 
            total_time_seconds,
            session_start,
            session_data
        ) VALUES (?, ?, ?, ?, NOW(), ?)
    ";
    
    // Convert total_time from minutes to seconds for compatibility
    $total_time_seconds = round($data['total_time'] * 60);
    
    // Store detailed analytics in JSON format for future use
    $session_data = json_encode([
        'focused_time' => $data['focused_time'],
        'unfocused_time' => $data['unfocused_time'],
        'focus_percentage' => $data['focus_percentage'],
        'focus_sessions' => $data['focus_sessions'] ?? 0,
        'unfocus_sessions' => $data['unfocus_sessions'] ?? 0,
        'session_type' => $data['session_type'] ?? 'enhanced_cv_tracking',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->execute([
        $data['user_id'],
        $data['module_id'],
        $data['section_id'] ?? null,
        $total_time_seconds,
        $session_data
    ]);
    
    $record_id = $conn->lastInsertId();
    
    // // Also update user progress for module completion tracking
    // if (isset($data['module_id']) && $data['total_time'] > 0) {
    //     $progress_sql = "
    //         INSERT INTO user_progress (user_id, module_id, time_spent, last_accessed)
    //         VALUES (?, ?, ?, NOW())
    //         ON DUPLICATE KEY UPDATE
    //         time_spent = time_spent + VALUES(time_spent),
    //         last_accessed = VALUES(last_accessed)
    //     ";
        
    //     $progress_stmt = $conn->prepare($progress_sql);
    //     $progress_stmt->execute([
    //         $data['user_id'],
    //         $data['module_id'],
    //         $total_time_seconds  // Also in seconds for consistency
    //     ]);
    // }
    
      // Also update user progress for module completion tracking
    if (isset($data['module_id']) && $data['total_time'] > 0) {
        $progress_sql = "
            INSERT INTO user_progress (user_id, module_id, completion_percentage, last_accessed)
            VALUES (?, ?, ?, NOW())
        ";
        
        $progress_stmt = $conn->prepare($progress_sql);
        $progress_stmt->execute([
            $data['user_id'],
            $data['module_id'],
            $total_time_seconds  // Also in seconds for consistency
        ]);
    }
    echo json_encode([
        'success' => true,
        'message' => "Eye tracking data saved successfully",
        'record_id' => $record_id,
        'data_saved' => [
            'total_time_seconds' => $total_time_seconds,
            'focused_time_minutes' => $data['focused_time'],
            'unfocused_time_minutes' => $data['unfocused_time'],
            'focus_percentage' => $data['focus_percentage']
        ],
        'dashboard_compatible' => true
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
