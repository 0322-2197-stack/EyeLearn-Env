<?php
function getWeeklyFocusScore($conn, $user_id) {
    // Calculate focus from eye_tracking_sessions (where Python service saves data)
    $query = "SELECT 
        AVG(CASE 
            WHEN total_time_seconds > 0 
            THEN (focused_time_seconds / total_time_seconds) * 100 
            ELSE 0 
        END) as avg_focus,
        AVG(CASE 
            WHEN date(created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 14 DAY) 
            AND date(created_at) < DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
            AND total_time_seconds > 0
            THEN (focused_time_seconds / total_time_seconds) * 100 
            ELSE NULL
        END) as last_week_focus
        FROM eye_tracking_sessions 
        WHERE user_id = ? 
        AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 14 DAY)";
    
    $stmt = $conn->prepare($query);
    
    // Check if prepare() failed
    if ($stmt === false) {
        error_log("Failed to prepare query in getWeeklyFocusScore: " . $conn->error);
        return [
            'current_score' => 0,
            'previous_score' => 0
        ];
    }
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    return [
        'current_score' => round($data['avg_focus'] ?? 0),
        'previous_score' => round($data['last_week_focus'] ?? 0)
    ];
}


function getComprehensionLevel($conn, $user_id) {
    // Use module_completions table instead of quiz_results
    $query = "SELECT 
        AVG(final_quiz_score) as avg_score,
        COUNT(*) as total_assessments,
        MAX(completion_date) as latest_assessment
        FROM module_completions 
        WHERE user_id = ? 
        AND completion_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
        AND final_quiz_score IS NOT NULL";
    
    $stmt = $conn->prepare($query);
    
    // Check if prepare() failed
    if ($stmt === false) {
        error_log("Failed to prepare query in getComprehensionLevel: " . $conn->error);
        return ['level' => 'Beginner', 'percentage' => 50];
    }
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $avg_score = $data['avg_score'] ?? 0;
    $total_assessments = $data['total_assessments'] ?? 0;
    
    if ($avg_score >= 90) {
        return ['level' => 'Expert', 'percentage' => 95];
    } elseif ($avg_score >= 80) {
        return ['level' => 'Advanced', 'percentage' => 85];
    } elseif ($avg_score >= 70) {
        return ['level' => 'Intermediate', 'percentage' => 70];
    } else {
        return ['level' => 'Beginner', 'percentage' => 50];
    }
}

