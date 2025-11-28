<?php
// admin/database/get_dashboard_data.php
header('Content-Type: application/json');

// Database connection
$conn = new mysqli('localhost', 'root', '', 'elearn_db');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

try {
    $dashboardData = [];
    
    // 1. Get total students count
    $studentCountQuery = "SELECT COUNT(*) as total_students FROM users WHERE role = 'student'";
    $result = $conn->query($studentCountQuery);
    $dashboardData['total_students'] = $result->fetch_assoc()['total_students'];
    
    // 2. Get total active modules count
    $moduleCountQuery = "SELECT COUNT(*) as total_modules FROM modules WHERE status = 'published'";
    $result = $conn->query($moduleCountQuery);
    $dashboardData['total_modules'] = $result->fetch_assoc()['total_modules'];
    
    // 3. Calculate completion rate (users who have made progress vs total users)
    $progressQuery = "SELECT 
        COUNT(DISTINCT up.user_id) as users_with_progress,
        (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students
        FROM user_progress up 
        WHERE up.completion_percentage > 0";
    $result = $conn->query($progressQuery);
    $progressData = $result->fetch_assoc();
    $completion_rate = $progressData['total_students'] > 0 ? 
        ($progressData['users_with_progress'] / $progressData['total_students']) * 100 : 0;
    $dashboardData['completion_rate'] = round($completion_rate, 1);
    
    // 4. Calculate average score from user progress
    $avgScoreQuery = "SELECT AVG(completion_percentage) as avg_score FROM user_progress WHERE completion_percentage > 0";
    $result = $conn->query($avgScoreQuery);
    $avgScore = $result->fetch_assoc();
    $dashboardData['average_score'] = round($avgScore['avg_score'] ?? 0, 1);
    
    // 5. Get gender distribution
    $genderQuery = "SELECT 
        gender,
        COUNT(*) as count,
        (COUNT(*) * 100.0 / (SELECT COUNT(*) FROM users WHERE role = 'student' AND gender != '')) as percentage
        FROM users 
        WHERE role = 'student' AND gender != '' 
        GROUP BY gender";
    $result = $conn->query($genderQuery);
    $genderData = [];
    while ($row = $result->fetch_assoc()) {
        $genderData[] = [
            'gender' => $row['gender'],
            'count' => (int)$row['count'],
            'percentage' => round($row['percentage'], 1)
        ];
    };
    $dashboardData['gender_distribution'] = $genderData;
    
    // 6. Get focus time data by gender from eye tracking sessions (improved filtering)
    $focusTimeQuery = "SELECT 
        u.gender,
        AVG(CASE WHEN ets.total_time_seconds BETWEEN 30 AND 7200 THEN ets.total_time_seconds ELSE NULL END) as avg_focus_time_seconds,
        COUNT(CASE WHEN ets.total_time_seconds BETWEEN 30 AND 7200 THEN 1 ELSE NULL END) as session_count
        FROM eye_tracking_sessions ets
        JOIN users u ON ets.user_id = u.id
        WHERE u.gender != '' AND u.gender IS NOT NULL
        GROUP BY u.gender
        HAVING session_count > 0";
    $result = $conn->query($focusTimeQuery);
    $focusTimeData = [];
    while ($row = $result->fetch_assoc()) {
        $focusTimeData[] = [
            'gender' => $row['gender'],
            'avg_focus_time_minutes' => round($row['avg_focus_time_seconds'] / 60, 1),
            'session_count' => (int)$row['session_count']
        ];
    }
    $dashboardData['focus_time_by_gender'] = $focusTimeData;
    
    // 7. Get student performance data (top 10 students) with improved focus time filtering
    $studentPerformanceQuery = "SELECT 
        u.id,
        u.first_name,
        u.last_name,
        u.email,
        u.gender,
        COALESCE(AVG(up.completion_percentage), 0) as avg_completion,
        COALESCE(AVG(CASE WHEN ets.total_time_seconds BETWEEN 30 AND 7200 THEN ets.total_time_seconds ELSE NULL END), 0) as avg_focus_time_seconds,
        COUNT(DISTINCT up.module_id) as modules_enrolled,
        COUNT(CASE WHEN ets.total_time_seconds BETWEEN 30 AND 7200 THEN 1 ELSE NULL END) as valid_sessions
        FROM users u
        LEFT JOIN user_progress up ON u.id = up.user_id
        LEFT JOIN eye_tracking_sessions ets ON u.id = ets.user_id
        WHERE u.role = 'student'
        GROUP BY u.id, u.first_name, u.last_name, u.email, u.gender
        ORDER BY avg_completion DESC, avg_focus_time_seconds DESC
        LIMIT 10";
    $result = $conn->query($studentPerformanceQuery);
    $studentPerformance = [];
    while ($row = $result->fetch_assoc()) {
        $avgFocusTimeSeconds = $row['avg_focus_time_seconds'] ?? 0;
        $avgFocusTimeMinutes = $avgFocusTimeSeconds > 0 ? round($avgFocusTimeSeconds / 60, 1) : 0;
        $validSessions = (int)$row['valid_sessions'];
        
        $studentPerformance[] = [
            'id' => (int)$row['id'],
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'email' => $row['email'],
            'gender' => $row['gender'] ?: 'Not specified',
            'avg_completion' => round($row['avg_completion'], 1),
            'avg_focus_time_seconds' => $avgFocusTimeSeconds,
            'avg_focus_time_minutes' => $avgFocusTimeMinutes,
            'modules_enrolled' => (int)$row['modules_enrolled'],
            'valid_sessions' => $validSessions,
            'initials' => strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1))
        ];
    }
    $dashboardData['student_performance'] = $studentPerformance;
    
    // 8. Get focus time trends by module for chart data
    $moduleAnalyticsQuery = "SELECT 
        m.title as module_name,
        u.gender,
        AVG(CASE WHEN ets.total_time_seconds > 0 THEN ets.total_time_seconds ELSE NULL END) as avg_time_seconds
        FROM eye_tracking_sessions ets
        JOIN users u ON ets.user_id = u.id
        JOIN modules m ON ets.module_id = m.id
        WHERE u.gender != '' AND ets.total_time_seconds > 0
        GROUP BY m.id, m.title, u.gender
        ORDER BY m.id, u.gender";
    $result = $conn->query($moduleAnalyticsQuery);
    $moduleAnalytics = [];
    while ($row = $result->fetch_assoc()) {
        $moduleAnalytics[] = [
            'module_name' => $row['module_name'],
            'gender' => $row['gender'],
            'avg_time_minutes' => round($row['avg_time_seconds'] / 60, 1)
        ];
    }
    $dashboardData['module_analytics'] = $moduleAnalytics;
    
    // 9. Get recent activity stats for growth indicators
    $recentStatsQuery = "SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'student' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as new_students_30d,
        (SELECT COUNT(*) FROM users WHERE role = 'student' AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)) as new_students_prev_30d,
        (SELECT COUNT(*) FROM eye_tracking_sessions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as sessions_30d,
        (SELECT COUNT(*) FROM eye_tracking_sessions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)) as sessions_prev_30d";
    $result = $conn->query($recentStatsQuery);
    $recentStats = $result->fetch_assoc();

   // 10. Time to complete module by gender (fixed version)
        $timeToCompleteQuery = "
            SELECT 
                gender,
                module_name,
                ROUND(AVG(user_total_minutes), 1) AS avg_completion_time_minutes
            FROM (
                SELECT 
                    u.id AS user_id,
                    u.gender,
                    m.title AS module_name,
                    SUM(ets.total_time_seconds) / 60 AS user_total_minutes
                FROM eye_tracking_sessions ets
                JOIN users u ON ets.user_id = u.id
                JOIN modules m ON ets.module_id = m.id
                WHERE ets.total_time_seconds BETWEEN 30 AND 7200
                AND u.gender != ''
                GROUP BY u.id, u.gender, m.id, m.title
            ) AS user_module_totals
            GROUP BY gender, module_name
            ORDER BY module_name, gender;
        ";

        $result = $conn->query($timeToCompleteQuery);
        $timeToComplete = [];
        while ($row = $result->fetch_assoc()) {
            $timeToComplete[] = [
                'gender' => $row['gender'],
                'module_name' => $row['module_name'],
                'avg_completion_time_minutes' => (float)$row['avg_completion_time_minutes']
            ];
        }
        $dashboardData['time_to_complete_by_gender'] = $timeToComplete;
            // 10B. Total time to complete (aggregate by gender)
        $totalTimeByGenderQuery = "
            SELECT 
                u.gender,
                ROUND(SUM(ets.total_time_seconds) / 60, 1) AS total_time_minutes
            FROM eye_tracking_sessions ets
            JOIN users u ON ets.user_id = u.id
            WHERE u.gender IS NOT NULL AND u.gender != ''
            AND ets.total_time_seconds BETWEEN 30 AND 7200
            GROUP BY u.gender;
        ";
        $result = $conn->query($totalTimeByGenderQuery);
        $totalTimeByGender = [];
        while ($row = $result->fetch_assoc()) {
            $totalTimeByGender[] = [
                'gender' => $row['gender'],
                'total_time_minutes' => (float)$row['total_time_minutes']
            ];
        }
        $dashboardData['total_time_by_gender'] = $totalTimeByGender;

    //end 10.

    // Calculate growth percentages
    $student_growth = 0;
    if ($recentStats['new_students_prev_30d'] > 0) {
        $student_growth = (($recentStats['new_students_30d'] - $recentStats['new_students_prev_30d']) / $recentStats['new_students_prev_30d']) * 100;
    } elseif ($recentStats['new_students_30d'] > 0) {
        $student_growth = 100; // 100% growth if no previous data but have current data
    }
    
    $dashboardData['growth_stats'] = [
        'student_growth_percentage' => round($student_growth, 1),
        'new_students_this_month' => (int)$recentStats['new_students_30d'],
        'sessions_this_month' => (int)$recentStats['sessions_30d']
    ];
    
    echo json_encode($dashboardData);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch dashboard data: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>
