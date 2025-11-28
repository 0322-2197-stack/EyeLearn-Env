<?php
header('Content-Type: application/json');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
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

$user_id = $_SESSION['user_id'];

try {
    // Get real-time overall user statistics from current sessions
    $stats_query = "
        SELECT 
            COUNT(DISTINCT COALESCE(ets.module_id, 0)) as modules_studied,
            COALESCE(SUM(ets.total_time_seconds), 0) as total_study_time,
            COUNT(ets.id) as total_sessions,
            COALESCE(AVG(ets.total_time_seconds), 0) as avg_session_time,
            COALESCE(MAX(ets.total_time_seconds), 0) as longest_session,
            MIN(ets.created_at) as first_session_date,
            MAX(ets.last_updated) as last_session_date
        FROM eye_tracking_sessions ets 
        WHERE ets.user_id = ?
    ";
    
    $stmt = $conn->prepare($stats_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stats_result = $stmt->get_result();
    $overall_stats = $stats_result->fetch_assoc();

    // If no data exists, create default values
    if ($overall_stats['total_sessions'] == 0) {
        $overall_stats = [
            'modules_studied' => 0,
            'total_study_time' => 0,
            'total_sessions' => 0,
            'avg_session_time' => 0,
            'longest_session' => 0,
            'first_session_date' => null,
            'last_session_date' => null
        ];
    }

    // Get recent session data for trends (real sessions from database)
    $recent_sessions_query = "
        SELECT 
            DATE(ets.created_at) as session_date,
            SUM(ets.total_time_seconds) as daily_study_time,
            COUNT(ets.id) as daily_sessions,
            AVG(ets.total_time_seconds) as avg_session_duration,
            m.title as module_title
        FROM eye_tracking_sessions ets
        LEFT JOIN modules m ON ets.module_id = m.id
        WHERE ets.user_id = ?
        AND ets.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(ets.created_at), m.title
        ORDER BY session_date DESC, daily_study_time DESC
        LIMIT 30
    ";
    
    $stmt = $conn->prepare($recent_sessions_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $sessions_result = $stmt->get_result();
    
    $analytics_data = [];
    while ($row = $sessions_result->fetch_assoc()) {
        $analytics_data[] = [
            'date' => $row['session_date'],
            'total_focus_time' => $row['daily_study_time'], // Using actual study time
            'session_count' => $row['daily_sessions'],
            'average_session_time' => round($row['avg_session_duration']),
            'max_continuous_time' => $row['daily_study_time'], // Simplified for now
            'module_title' => $row['module_title'] ?: 'Module Study'
        ];
    }

    // Get module-specific performance
    $module_performance_query = "
        SELECT 
            m.title as module_title,
            m.id as module_id,
            SUM(ets.total_time_seconds) as total_time,
            COUNT(ets.id) as session_count,
            AVG(ets.total_time_seconds) as avg_session_time,
            MAX(ets.total_time_seconds) as best_session,
            MAX(ets.last_updated) as last_studied
        FROM eye_tracking_sessions ets
        JOIN modules m ON ets.module_id = m.id
        WHERE ets.user_id = ?
        GROUP BY ets.module_id, m.title, m.id
        ORDER BY total_time DESC
    ";
    
    $stmt = $conn->prepare($module_performance_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $module_result = $stmt->get_result();
    
    $module_performance = [];
    while ($row = $module_result->fetch_assoc()) {
        $module_performance[] = $row;
    }

    // Get focus trends (last 7 days) from real session data
    $focus_trends_query = "
        SELECT 
            DATE(ets.created_at) as study_date,
            SUM(ets.total_time_seconds) as daily_focus_time,
            COUNT(ets.id) as daily_sessions,
            AVG(ets.total_time_seconds) as avg_session_duration
        FROM eye_tracking_sessions ets
        WHERE ets.user_id = ? 
        AND ets.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(ets.created_at)
        ORDER BY study_date ASC
    ";
    
    $stmt = $conn->prepare($focus_trends_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $trends_result = $stmt->get_result();
    
    $focus_trends = [];
    while ($row = $trends_result->fetch_assoc()) {
        $focus_trends[] = $row;
    }

    // Get current active session data if available
    $current_session_query = "
        SELECT 
            ets.module_id,
            ets.section_id,
            ets.total_time_seconds,
            ets.created_at,
            m.title as module_title
        FROM eye_tracking_sessions ets
        LEFT JOIN modules m ON ets.module_id = m.id
        WHERE ets.user_id = ?
        AND ets.last_updated > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ORDER BY ets.last_updated DESC
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($current_session_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $current_result = $stmt->get_result();
    $current_session = $current_result->fetch_assoc();

    // Calculate derived metrics from real data
    $total_hours = round(($overall_stats['total_study_time'] ?? 0) / 3600, 1);
    $avg_session_minutes = round(($overall_stats['avg_session_time'] ?? 0) / 60, 1);
    $longest_session_minutes = round(($overall_stats['longest_session'] ?? 0) / 60, 1);
    
    // Calculate focus efficiency from real session data (simplified calculation)
    $focus_efficiency = 0;
    if ($overall_stats['total_sessions'] > 0 && $overall_stats['avg_session_time'] > 0) {
        // Simple efficiency calculation: longer sessions indicate better focus
        // You can enhance this with actual focus/unfocus data from the CV service
        if ($avg_session_minutes >= 20) {
            $focus_efficiency = min(85 + ($avg_session_minutes - 20) * 0.5, 95);
        } else if ($avg_session_minutes >= 10) {
            $focus_efficiency = 60 + ($avg_session_minutes - 10) * 2.5;
        } else {
            $focus_efficiency = max(30, $avg_session_minutes * 6);
        }
        $focus_efficiency = round($focus_efficiency, 1);
    }

    // Prepare response with real data
    $response = [
        'success' => true,
        'data' => [
            'overall_stats' => [
                'modules_studied' => $overall_stats['modules_studied'] ?? 0,
                'total_study_time_hours' => $total_hours,
                'total_sessions' => $overall_stats['total_sessions'] ?? 0,
                'avg_session_minutes' => $avg_session_minutes,
                'longest_session_minutes' => $longest_session_minutes,
                'focus_efficiency_percent' => $focus_efficiency,
                'first_session' => $overall_stats['first_session_date'],
                'last_session' => $overall_stats['last_session_date']
            ],
            'recent_analytics' => $analytics_data,
            'module_performance' => $module_performance,
            'focus_trends' => $focus_trends,
            'current_session' => $current_session,
            'insights' => [
                'best_study_day' => null,
                'improvement_suggestion' => null,
                'streak_info' => null
            ],
            'realtime_data' => [
                'last_updated' => date('Y-m-d H:i:s'),
                'is_currently_studying' => !empty($current_session),
                'active_module' => $current_session['module_title'] ?? null
            ]
        ]
    ];

    // Calculate insights
    if (count($focus_trends) > 0) {
        // Find best study day
        $best_day = array_reduce($focus_trends, function($carry, $item) {
            return ($carry === null || $item['daily_focus_time'] > $carry['daily_focus_time']) ? $item : $carry;
        });
        $response['data']['insights']['best_study_day'] = $best_day;
        
        // Simple improvement suggestion
        if ($focus_efficiency < 60) {
            $response['data']['insights']['improvement_suggestion'] = "Try shorter, more focused study sessions to improve concentration.";
        } else if ($focus_efficiency > 80) {
            $response['data']['insights']['improvement_suggestion'] = "Excellent focus! Consider challenging yourself with more complex modules.";
        } else {
            $response['data']['insights']['improvement_suggestion'] = "Good focus levels. Regular breaks might help maintain concentration.";
        }
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch analytics data: ' . $e->getMessage()]);
}

$conn->close();
?>
