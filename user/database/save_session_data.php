<?php
header('Content-Type: application/json');
session_start();

// -------------------------------------------------------------------------
// Determine user ID
// -------------------------------------------------------------------------
// Primary source: PHP session (works well on traditional hosting)
// Fallback (for Railway/stateless hosting): explicit user_id from JSON body.

// Read raw input once so we can potentially reuse it below
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

// On Railway, sessions may not persist properly. If we detect a Railway
// environment and there is no session, fall back to the user_id sent by JS.
$isRailway = getenv('RAILWAY_ENVIRONMENT') || getenv('RAILWAY_STATIC_URL');
if ($user_id === null && $isRailway && is_array($input) && isset($input['user_id'])) {
    $user_id = (int)$input['user_id'];
}

if ($user_id === null) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
try {
    $conn = getMysqliConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Get JSON input (reuse the already-decoded payload when possible)
if (!is_array($input)) {
    $input = json_decode($rawInput !== false ? $rawInput : '', true);
}

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit();
}

try {
    $module_id = $input['module_id'] ?? null;
    $section_id = $input['section_id'] ?? null;
    $session_time = $input['session_time'] ?? 0;
    $focus_data = $input['focus_data'] ?? [];

    if (!$module_id) {
        throw new Exception('Module ID is required');
    }

    // Extract focus time data
    $focused_time = isset($focus_data['focused_time']) ? (int)$focus_data['focused_time'] : 0;
    $unfocused_time = isset($focus_data['unfocused_time']) ? (int)$focus_data['unfocused_time'] : 0;

    // Insert or update session data - NOW INCLUDES FOCUSED/UNFOCUSED TIMES
    $session_sql = "
        INSERT INTO eye_tracking_sessions 
        (user_id, module_id, section_id, total_time_seconds, focused_time_seconds, unfocused_time_seconds, session_type, created_at, last_updated) 
        VALUES (?, ?, ?, ?, ?, ?, 'viewing', NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
        total_time_seconds = total_time_seconds + VALUES(total_time_seconds),
        focused_time_seconds = focused_time_seconds + VALUES(focused_time_seconds),
        unfocused_time_seconds = unfocused_time_seconds + VALUES(unfocused_time_seconds),
        last_updated = NOW()
    ";

    $stmt = $conn->prepare($session_sql);
    $stmt->bind_param('iiiiii', $user_id, $module_id, $section_id, $session_time, $focused_time, $unfocused_time);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to save session data');
    }

    // If focus data is provided, save more detailed analytics using NEW column names
    if (!empty($focus_data)) {
        $focused_time = $focus_data['focused_time'] ?? 0;
        $unfocused_time = $focus_data['unfocused_time'] ?? 0;
        $focus_percentage = $focus_data['focus_percentage'] ?? 0;

        // Update or insert daily analytics with NEW column names
        $analytics_sql = "
            INSERT INTO eye_tracking_analytics 
            (user_id, module_id, section_id, date, total_focused_time, total_unfocused_time, 
             focus_percentage, session_count, average_session_time, max_continuous_time, created_at, updated_at) 
            VALUES (?, ?, ?, CURDATE(), ?, ?, ?, 1, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
            total_focused_time = total_focused_time + VALUES(total_focused_time),
            total_unfocused_time = total_unfocused_time + VALUES(total_unfocused_time),
            session_count = session_count + 1,
            average_session_time = (total_focused_time + VALUES(total_focused_time)) / (session_count + 1),
            max_continuous_time = GREATEST(max_continuous_time, VALUES(max_continuous_time)),
            updated_at = NOW()
        ";

        $stmt = $conn->prepare($analytics_sql);
        $stmt->bind_param('iiiiidii', 
            $user_id, 
            $module_id, 
            $section_id, 
            $focused_time,
            $unfocused_time,
            $focus_percentage,
            $session_time, 
            $session_time
        );
        
        $stmt->execute(); // Don't fail if analytics insert fails
    }
    
    if ($module_id) {
        // Update user progress
        $completion_percentage = isset($input['completion_percentage'])
            ? floatval($input['completion_percentage'])
            : 0;

        $progress_sql = "
            INSERT INTO user_progress 
            (user_id, module_id, completion_percentage, last_accessed)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                completion_percentage = GREATEST(completion_percentage, VALUES(completion_percentage)),
                last_accessed = NOW()
        ";

        $stmt = $conn->prepare($progress_sql);
        $stmt->bind_param('iid', $user_id, $module_id, $completion_percentage);
        $stmt->execute(); // Don't fail entire request if this fails
    }

    echo json_encode([
        'success' => true,
        'message' => 'Session data saved successfully',
        'session_time' => $session_time,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save session data: ' . $e->getMessage()]);
}

$conn->close();
?>
