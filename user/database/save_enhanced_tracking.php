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
    $user_id = (int)$data['user_id'];
    $module_id = (int)$data['module_id'];
    $section_id = isset($data['section_id']) && $data['section_id'] !== '' 
        ? (int)$data['section_id'] 
        : null;
    
    // Metrics are already in seconds from the Python service
    $total_time_seconds = isset($data['total_time']) ? (int)round($data['total_time']) : 0;
    $focused_time_seconds = isset($data['focused_time']) ? (int)round($data['focused_time']) : 0;
    $unfocused_time_seconds = isset($data['unfocused_time']) ? (int)round($data['unfocused_time']) : 0;
    $focus_percentage = isset($data['focus_percentage']) ? (float)$data['focus_percentage'] : 0;
    
    // Store detailed analytics in JSON format for future diagnostics
    $session_payload = [
        'focused_time' => $focused_time_seconds,
        'unfocused_time' => $unfocused_time_seconds,
        'focus_percentage' => $focus_percentage,
        'focus_sessions' => $data['focus_sessions'] ?? 0,
        'unfocus_sessions' => $data['unfocus_sessions'] ?? 0,
        'session_type' => $data['session_type'] ?? 'enhanced_cv_tracking',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $session_data = json_encode($session_payload, JSON_UNESCAPED_UNICODE) ?: '{}';
    
    $insert_sql = "
        INSERT INTO eye_tracking_sessions (
            user_id,
            module_id,
            section_id,
            total_time_seconds,
            focused_time_seconds,
            unfocused_time_seconds,
            session_type,
            created_at,
            last_updated,
            session_data
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)
    ";
    
    $session_type = $data['session_type'] ?? 'viewing'; // From Python service
    
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->execute([
        $user_id,
        $module_id,
        $section_id,
        $total_time_seconds,
        $focused_time_seconds,
        $unfocused_time_seconds,
        $session_type,  // Add session_type parameter
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
    if (isset($data['completion_percentage'])) {
        $progress_sql = "
            INSERT INTO user_progress (user_id, module_id, completion_percentage, last_accessed)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                completion_percentage = GREATEST(completion_percentage, VALUES(completion_percentage)),
                last_accessed = VALUES(last_accessed)
        ";
        
        $progress_stmt = $conn->prepare($progress_sql);
        $progress_stmt->execute([
            $user_id,
            $module_id,
            (float)$data['completion_percentage']
        ]);
    }
    echo json_encode([
        'success' => true,
        'message' => "Eye tracking data saved successfully",
        'record_id' => $record_id,
        'data_saved' => [
            'total_time_seconds' => $total_time_seconds,
            'focused_time_seconds' => $focused_time_seconds,
            'unfocused_time_seconds' => $unfocused_time_seconds,
            'focus_percentage' => $focus_percentage
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
