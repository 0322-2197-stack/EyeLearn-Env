<?php
// admin/database/get_dashboard_data.php
header('Content-Type: application/json');

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
try {
    $conn = getMysqliConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

try {
    // Get module_id from query string if it exists
    $filter_module_id = isset($_GET['module_id']) ? (int)$_GET['module_id'] : null;

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
    
    // 7. Get student performance data - show ALL students, even if they don't have data for selected module
    // Support module filtering - if module_id is provided, filter by that module; otherwise show all modules aggregated
    $studentPerformanceModuleId = isset($_GET['student_performance_module_id']) ? (int)$_GET['student_performance_module_id'] : null;
    
    // Check if section column exists
    $check_section = $conn->query("SHOW COLUMNS FROM users LIKE 'section'");
    $has_section = $check_section && $check_section->num_rows > 0;
    
    // Check if user_module_progress table exists (more accurate progress calculation)
    $tables = [];
    $tablesResult = $conn->query("SHOW TABLES");
    while ($tableRow = $tablesResult->fetch_array()) {
        $tables[] = $tableRow[0];
    }
    $hasUserModuleProgress = in_array('user_module_progress', $tables);
    
    if ($studentPerformanceModuleId) {
        // Filter by specific module - but show ALL students, even those without data for this module
        // For specific module: show final_quiz_score as fraction (score/total_questions)
        // Progress: Always calculate as percentage, ensure it's capped at 100%
        // Focus Time: SUM all focus time for this specific module to get total focus time for that module
        // Use subquery to pre-aggregate focus time to avoid duplication from JOINs
        if ($has_section) {
            $studentPerformanceQuery = "SELECT 
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                u.gender,
                u.section,
                LEAST(COALESCE(AVG(CASE WHEN up.module_id = ? THEN up.completion_percentage ELSE NULL END), NULL), 100.0) as avg_completion,
                mc.final_quiz_score as quiz_score,
                qc.total_questions as total_questions,
                COALESCE(focus_data.total_focus_time_seconds, NULL) as total_focus_time_seconds,
                COALESCE(focus_data.total_sessions, 0) as total_sessions,
                COALESCE(focus_data.valid_sessions, 0) as valid_sessions
                FROM users u
                LEFT JOIN user_progress up ON u.id = up.user_id
                LEFT JOIN module_completions mc ON u.id = mc.user_id AND mc.module_id = ?
                LEFT JOIN (
                    SELECT fq.module_id, COUNT(fqq.id) as total_questions
                    FROM final_quizzes fq
                    LEFT JOIN final_quiz_questions fqq ON fq.id = fqq.quiz_id
                    GROUP BY fq.module_id
                ) qc ON mc.module_id = qc.module_id
                LEFT JOIN (
                    SELECT 
                        user_id,
                        SUM(CASE WHEN total_time_seconds BETWEEN 30 AND 7200 THEN total_time_seconds ELSE 0 END) as total_focus_time_seconds,
                        COUNT(DISTINCT id) as total_sessions,
                        COUNT(CASE WHEN total_time_seconds BETWEEN 30 AND 7200 THEN 1 ELSE NULL END) as valid_sessions
                    FROM eye_tracking_sessions
                    WHERE module_id = ?
                    GROUP BY user_id
                ) focus_data ON u.id = focus_data.user_id
                WHERE u.role = 'student'
                GROUP BY u.id, u.first_name, u.last_name, u.email, u.gender, u.section, mc.final_quiz_score, qc.total_questions, focus_data.total_focus_time_seconds, focus_data.total_sessions, focus_data.valid_sessions
                ORDER BY u.id ASC";
        } else {
            $studentPerformanceQuery = "SELECT 
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                u.gender,
                NULL as section,
                LEAST(COALESCE(AVG(CASE WHEN up.module_id = ? THEN up.completion_percentage ELSE NULL END), NULL), 100.0) as avg_completion,
                mc.final_quiz_score as quiz_score,
                qc.total_questions as total_questions,
                COALESCE(focus_data.total_focus_time_seconds, NULL) as total_focus_time_seconds,
                COALESCE(focus_data.total_sessions, 0) as total_sessions,
                COALESCE(focus_data.valid_sessions, 0) as valid_sessions
                FROM users u
                LEFT JOIN user_progress up ON u.id = up.user_id
                LEFT JOIN module_completions mc ON u.id = mc.user_id AND mc.module_id = ?
                LEFT JOIN (
                    SELECT fq.module_id, COUNT(fqq.id) as total_questions
                    FROM final_quizzes fq
                    LEFT JOIN final_quiz_questions fqq ON fq.id = fqq.quiz_id
                    GROUP BY fq.module_id
                ) qc ON mc.module_id = qc.module_id
                LEFT JOIN (
                    SELECT 
                        user_id,
                        SUM(CASE WHEN total_time_seconds BETWEEN 30 AND 7200 THEN total_time_seconds ELSE 0 END) as total_focus_time_seconds,
                        COUNT(DISTINCT id) as total_sessions,
                        COUNT(CASE WHEN total_time_seconds BETWEEN 30 AND 7200 THEN 1 ELSE NULL END) as valid_sessions
                    FROM eye_tracking_sessions
                    WHERE module_id = ?
                    GROUP BY user_id
                ) focus_data ON u.id = focus_data.user_id
                WHERE u.role = 'student'
                GROUP BY u.id, u.first_name, u.last_name, u.email, u.gender, mc.final_quiz_score, qc.total_questions, focus_data.total_focus_time_seconds, focus_data.total_sessions, focus_data.valid_sessions
                ORDER BY u.id ASC";
        }
        
        $stmt = $conn->prepare($studentPerformanceQuery);
        $stmt->bind_param("iii", 
            $studentPerformanceModuleId, // for completion avg
            $studentPerformanceModuleId, // for module_completions join
            $studentPerformanceModuleId  // for focus_data subquery (module filter)
        );
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Show all modules aggregated - show ALL students
        // For all modules: calculate percentage = (SUM(final_quiz_scores) / SUM(max_possible_scores)) * 100, capped at 100%
        // Progress: Always calculate as percentage, ensure it's capped at 100%
        // Focus Time: SUM all focus time from all modules to get total combined focus time
        // Use subquery to pre-aggregate focus time to avoid duplication from JOINs
        if ($has_section) {
            $studentPerformanceQuery = "SELECT 
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                u.gender,
                u.section,
                LEAST(COALESCE(AVG(up.completion_percentage), NULL), 100.0) as avg_completion,
                CASE 
                    WHEN SUM(mc.final_quiz_score) IS NOT NULL AND SUM(qc.total_questions) IS NOT NULL AND SUM(qc.total_questions) > 0
                    THEN LEAST(ROUND((SUM(mc.final_quiz_score) / SUM(qc.total_questions)) * 100, 1), 100.0)
                    ELSE NULL
                END as avg_quiz_score,
                COALESCE(focus_data.total_focus_time_seconds, NULL) as total_focus_time_seconds,
                COALESCE(focus_data.total_sessions, 0) as total_sessions,
                COALESCE(focus_data.valid_sessions, 0) as valid_sessions
                FROM users u
                LEFT JOIN user_progress up ON u.id = up.user_id
                LEFT JOIN module_completions mc ON u.id = mc.user_id
                LEFT JOIN (
                    SELECT fq.module_id, COUNT(fqq.id) as total_questions
                    FROM final_quizzes fq
                    LEFT JOIN final_quiz_questions fqq ON fq.id = fqq.quiz_id
                    GROUP BY fq.module_id
                ) qc ON mc.module_id = qc.module_id
                LEFT JOIN (
                    SELECT 
                        user_id,
                        SUM(CASE WHEN total_time_seconds BETWEEN 30 AND 7200 THEN total_time_seconds ELSE 0 END) as total_focus_time_seconds,
                        COUNT(DISTINCT id) as total_sessions,
                        COUNT(CASE WHEN total_time_seconds BETWEEN 30 AND 7200 THEN 1 ELSE NULL END) as valid_sessions
                    FROM eye_tracking_sessions
                    GROUP BY user_id
                ) focus_data ON u.id = focus_data.user_id
                WHERE u.role = 'student'
                GROUP BY u.id, u.first_name, u.last_name, u.email, u.gender, u.section, focus_data.total_focus_time_seconds, focus_data.total_sessions, focus_data.valid_sessions
                ORDER BY u.id ASC";
        } else {
            $studentPerformanceQuery = "SELECT 
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                u.gender,
                NULL as section,
                LEAST(COALESCE(AVG(up.completion_percentage), NULL), 100.0) as avg_completion,
                CASE 
                    WHEN SUM(mc.final_quiz_score) IS NOT NULL AND SUM(qc.total_questions) IS NOT NULL AND SUM(qc.total_questions) > 0
                    THEN LEAST(ROUND((SUM(mc.final_quiz_score) / SUM(qc.total_questions)) * 100, 1), 100.0)
                    ELSE NULL
                END as avg_quiz_score,
                COALESCE(focus_data.total_focus_time_seconds, NULL) as total_focus_time_seconds,
                COALESCE(focus_data.total_sessions, 0) as total_sessions,
                COALESCE(focus_data.valid_sessions, 0) as valid_sessions
                FROM users u
                LEFT JOIN user_progress up ON u.id = up.user_id
                LEFT JOIN module_completions mc ON u.id = mc.user_id
                LEFT JOIN (
                    SELECT fq.module_id, COUNT(fqq.id) as total_questions
                    FROM final_quizzes fq
                    LEFT JOIN final_quiz_questions fqq ON fq.id = fqq.quiz_id
                    GROUP BY fq.module_id
                ) qc ON mc.module_id = qc.module_id
                LEFT JOIN (
                    SELECT 
                        user_id,
                        SUM(CASE WHEN total_time_seconds BETWEEN 30 AND 7200 THEN total_time_seconds ELSE 0 END) as total_focus_time_seconds,
                        COUNT(DISTINCT id) as total_sessions,
                        COUNT(CASE WHEN total_time_seconds BETWEEN 30 AND 7200 THEN 1 ELSE NULL END) as valid_sessions
                    FROM eye_tracking_sessions
                    GROUP BY user_id
                ) focus_data ON u.id = focus_data.user_id
                WHERE u.role = 'student'
                GROUP BY u.id, u.first_name, u.last_name, u.email, u.gender, focus_data.total_focus_time_seconds, focus_data.total_sessions, focus_data.valid_sessions
                ORDER BY u.id ASC";
        }
        
        $result = $conn->query($studentPerformanceQuery);
    }
    $studentPerformance = [];
    while ($row = $result->fetch_assoc()) {
        // Check if data exists (NULL means no data for selected module)
        $hasCompletionData = $row['avg_completion'] !== null;
        // For specific module, check quiz_score and total_questions; for all modules, check avg_quiz_score
        if ($studentPerformanceModuleId) {
            $hasQuizData = isset($row['quiz_score']) && $row['quiz_score'] !== null && 
                          isset($row['total_questions']) && $row['total_questions'] !== null && 
                          $row['total_questions'] > 0;
        } else {
            $hasQuizData = isset($row['avg_quiz_score']) && $row['avg_quiz_score'] !== null;
        }
        // For individual modules: use total_focus_time_seconds (sum of all focus time for that specific module)
        // For all modules: use total_focus_time_seconds (sum of all focus time from all modules)
        // Both cases now use SUM, so we can use the same field name
        $hasFocusTimeData = isset($row['total_focus_time_seconds']) && $row['total_focus_time_seconds'] !== null;
        $focusTimeSeconds = $hasFocusTimeData ? (float)$row['total_focus_time_seconds'] : null;
        
        $focusTimeMinutes = $hasFocusTimeData && $focusTimeSeconds > 0 ? round($focusTimeSeconds / 60, 1) : null;
        $validSessions = (int)$row['valid_sessions'];
        $totalSessions = (int)$row['total_sessions'];
        
        // Calculate Average Focus Time Per Session = Total Focus Time / Total Valid Sessions
        // This works for both: specific module (uses that module's totals) and all modules (uses aggregated totals)
        $avgFocusTimePerSessionMinutes = null;
        if ($hasFocusTimeData && $focusTimeSeconds > 0 && $validSessions > 0) {
            // Convert total seconds to minutes, then divide by total valid sessions
            $avgFocusTimePerSessionMinutes = round(($focusTimeSeconds / 60) / $validSessions, 1);
        }
        
        // For quiz score: if specific module, it's fraction; if all modules, it's percentage
        $quizScore = null;
        $quizScoreDisplay = null;
        if ($hasQuizData) {
            if ($studentPerformanceModuleId) {
                // Specific module: show as fraction (score/total_questions)
                $score = (int)$row['quiz_score'];
                $total = (int)$row['total_questions'];
                if ($total > 0) {
                    $quizScore = $score; // Store raw score
                    $quizScoreDisplay = "{$score}/{$total}"; // Display as fraction
                } else {
                    $quizScore = null;
                    $quizScoreDisplay = null;
                }
            } else {
                // All modules: it's already a percentage (calculated in query)
                $quizScore = round($row['avg_quiz_score'], 1);
                $quizScoreDisplay = round($row['avg_quiz_score'], 1) . '%';
            }
        }
        
        // Calculate progress based on user_module_progress table (more accurate)
        // If there's only 1 module, show that module's progress
        // If there are many modules, calculate average progress across all modules
        $progressPercentage = null;
        $student_id = (int)$row['id'];
        
        if ($hasUserModuleProgress) {
            if ($studentPerformanceModuleId) {
                // Specific module: calculate progress for that module only
                $moduleIdEscaped = $conn->real_escape_string($studentPerformanceModuleId);
                $progressQuery = "SELECT 
                    ump.completed_sections,
                    (SELECT COUNT(*) FROM module_parts mp 
                     JOIN module_sections ms ON mp.id = ms.module_part_id 
                     WHERE mp.module_id = $moduleIdEscaped) as total_sections
                    FROM user_module_progress ump
                    WHERE ump.user_id = $student_id AND ump.module_id = $moduleIdEscaped";
                
                $progressResult = $conn->query($progressQuery);
                if ($progressResult && $progressResult->num_rows > 0) {
                    $progressRow = $progressResult->fetch_assoc();
                    $completedSections = json_decode($progressRow['completed_sections'] ?? '[]', true);
                    $completedCount = is_array($completedSections) ? count($completedSections) : 0;
                    $totalSections = (int)($progressRow['total_sections'] ?: 0);
                    
                    if ($totalSections > 0) {
                        $progressPercentage = min(100.0, max(0.0, round(($completedCount / $totalSections) * 100, 1)));
                    }
                }
            } else {
                // All modules: calculate progress for each module, then average
                $allModulesQuery = "SELECT 
                    ump.module_id,
                    ump.completed_sections,
                    (SELECT COUNT(*) FROM module_parts mp 
                     JOIN module_sections ms ON mp.id = ms.module_part_id 
                     WHERE mp.module_id = ump.module_id) as total_sections
                    FROM user_module_progress ump
                    WHERE ump.user_id = $student_id";
                
                $allModulesResult = $conn->query($allModulesQuery);
                if ($allModulesResult && $allModulesResult->num_rows > 0) {
                    $moduleProgresses = [];
                    
                    while ($moduleRow = $allModulesResult->fetch_assoc()) {
                        $completedSections = json_decode($moduleRow['completed_sections'] ?? '[]', true);
                        $completedCount = is_array($completedSections) ? count($completedSections) : 0;
                        $totalSections = (int)($moduleRow['total_sections'] ?: 0);
                        
                        if ($totalSections > 0) {
                            $moduleProgress = min(100.0, max(0.0, ($completedCount / $totalSections) * 100));
                            $moduleProgresses[] = $moduleProgress;
                        }
                    }
                    
                    // Calculate average progress across all modules
                    if (count($moduleProgresses) > 0) {
                        $progressPercentage = min(100.0, max(0.0, round(array_sum($moduleProgresses) / count($moduleProgresses), 1)));
                    }
                }
            }
        }
        
        // Fallback to user_progress table if user_module_progress doesn't exist or has no data
        if ($progressPercentage === null && $hasCompletionData && $row['avg_completion'] !== null) {
            $progressPercentage = min(100.0, max(0.0, round((float)$row['avg_completion'], 1)));
        }
        
        $studentPerformance[] = [
            'id' => (int)$row['id'],
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'email' => $row['email'],
            'gender' => $row['gender'] ?: 'Not specified',
            'section' => isset($row['section']) && $row['section'] ? $row['section'] : null,
            'avg_completion' => $progressPercentage, // Always a percentage (0-100)
            'avg_quiz_score' => $quizScore,
            'quiz_score_display' => $quizScoreDisplay, // Formatted display value
            'is_quiz_percentage' => !$studentPerformanceModuleId, // Flag to indicate if quiz score is percentage
            'avg_focus_time_seconds' => $focusTimeSeconds, // For individual module: total sum for that module; for all modules: total sum across all modules
            'avg_focus_time_minutes' => $focusTimeMinutes, // For individual module: total sum for that module; for all modules: total sum across all modules
            'avg_focus_time_per_session_minutes' => $avgFocusTimePerSessionMinutes, // Average Focus Time Per Session = Total Focus Time / Total Valid Sessions
            'total_sessions' => $totalSessions,
            'valid_sessions' => $validSessions,
            'has_data' => $hasCompletionData || $hasQuizData || $hasFocusTimeData, // Flag to indicate if user has any data
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

    // 11. Average final quiz score by gender (from quiz_results)
    // Support module filtering - if avg_score_module_id is provided, filter by that module
    $avgScoreModuleId = isset($_GET['avg_score_module_id']) ? (int)$_GET['avg_score_module_id'] : null;
    
    if ($avgScoreModuleId) {
        // Filter by specific module - calculate percentage correctly
        $avgScoreByGenderQuery = "
            SELECT 
                u.gender,
                ROUND(AVG(
                    CASE 
                        WHEN qr.percentage IS NOT NULL AND qr.percentage > 0 
                        THEN qr.percentage
                        WHEN qc.total_questions > 0 
                        THEN LEAST(ROUND((qr.score / qc.total_questions) * 100, 2), 100.00)
                        ELSE NULL
                    END
                ), 1) AS avg_score,
                COUNT(DISTINCT qr.user_id) AS student_count
            FROM quiz_results qr
            JOIN users u ON qr.user_id = u.id
            LEFT JOIN (
                SELECT fq.id as quiz_id, COUNT(fqq.id) as total_questions
                FROM final_quizzes fq
                LEFT JOIN final_quiz_questions fqq ON fq.id = fqq.quiz_id
                GROUP BY fq.id
            ) qc ON qr.quiz_id = qc.quiz_id
            WHERE u.gender IS NOT NULL AND u.gender != ''
            AND qr.module_id = ?
            GROUP BY u.gender
        ";
        $stmt = $conn->prepare($avgScoreByGenderQuery);
        $stmt->bind_param("i", $avgScoreModuleId);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Show all modules aggregated
        $avgScoreByGenderQuery = "
            SELECT 
                u.gender,
                ROUND(AVG(
                    CASE 
                        WHEN qr.percentage IS NOT NULL AND qr.percentage > 0 
                        THEN qr.percentage
                        WHEN qc.total_questions > 0 
                        THEN LEAST(ROUND((qr.score / qc.total_questions) * 100, 2), 100.00)
                        ELSE NULL
                    END
                ), 1) AS avg_score,
                COUNT(DISTINCT qr.user_id) AS student_count
            FROM quiz_results qr
            JOIN users u ON qr.user_id = u.id
            LEFT JOIN (
                SELECT fq.id as quiz_id, COUNT(fqq.id) as total_questions
                FROM final_quizzes fq
                LEFT JOIN final_quiz_questions fqq ON fq.id = fqq.quiz_id
                GROUP BY fq.id
            ) qc ON qr.quiz_id = qc.quiz_id
            WHERE u.gender IS NOT NULL AND u.gender != ''
            GROUP BY u.gender
        ";
        $result = $conn->query($avgScoreByGenderQuery);
    }
    
    $avgScoreByGender = [];
    while ($row = $result->fetch_assoc()) {
        $avgScoreByGender[] = [
            'gender' => $row['gender'],
            'avg_score' => (float)$row['avg_score'],
            'student_count' => (int)$row['student_count']
        ];
    }
    $dashboardData['avg_score_by_gender'] = $avgScoreByGender;

    // 12. Focus Time and Quiz Score Correlation Data
    // Corrected query: First, aggregate total focus time per user/module, then join with quiz results.
    // Use consistent filtering (30-7200 seconds) like other queries
    $correlationQuery = "
        SELECT
            qr.module_id,
            m.title as module_name,
            u.gender,
            qr.score as quiz_score,
            COALESCE(module_focus.total_focus_time_minutes, 0) as total_focus_time_minutes
        FROM quiz_results qr
        JOIN users u ON qr.user_id = u.id
        JOIN modules m ON qr.module_id = m.id
        LEFT JOIN (
            SELECT 
                user_id, 
                module_id, 
                SUM(CASE WHEN total_time_seconds BETWEEN 30 AND 7200 THEN total_time_seconds ELSE 0 END) / 60 as total_focus_time_minutes
            FROM eye_tracking_sessions
            WHERE total_time_seconds BETWEEN 30 AND 7200
            GROUP BY user_id, module_id
        ) AS module_focus ON qr.user_id = module_focus.user_id AND qr.module_id = module_focus.module_id
        WHERE u.role = 'student'
    ";

    if ($filter_module_id) {
        $correlationQuery .= " AND qr.module_id = ?";
        $stmt = $conn->prepare($correlationQuery);
        $stmt->bind_param("i", $filter_module_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($correlationQuery);
    }

    $correlationData = [];
    while($row = $result->fetch_assoc()) {
        // Only include data points with valid focus time (> 0)
        if ($row['total_focus_time_minutes'] > 0) {
            $correlationData[] = [
                'module_id' => (int)$row['module_id'],
                'module_name' => $row['module_name'],
                'gender' => $row['gender'],
                'quiz_score' => (float)$row['quiz_score'],
                'focus_time_minutes' => (float)$row['total_focus_time_minutes']
            ];
        }
    }
    $dashboardData['focus_score_correlation'] = $correlationData;

    // 13. Calculate Pearson Correlation for Focus Time vs. Score
    function calculate_pearson($x, $y) {
        $n = count($x);
        if ($n === 0 || $n !== count($y) || $n < 2) return null; // Need at least 2 data points

        $sum_x = array_sum($x);
        $sum_y = array_sum($y);
        $sum_x_sq = array_sum(array_map(function($i) { return $i * $i; }, $x));
        $sum_y_sq = array_sum(array_map(function($i) { return $i * $i; }, $y));

        $p_sum = 0;
        for ($i = 0; $i < $n; $i++) {
            $p_sum += $x[$i] * $y[$i];
        }

        $num = $p_sum - ($sum_x * $sum_y / $n);
        $den = sqrt(($sum_x_sq - pow($sum_x, 2) / $n) * ($sum_y_sq - pow($sum_y, 2) / $n));

        if ($den == 0) return null;

        return $num / $den;
    }

    // Separate data by gender and overall
    $male_focus_times = [];
    $male_scores = [];
    $female_focus_times = [];
    $female_scores = [];
    $all_focus_times = [];
    $all_scores = [];

    foreach ($correlationData as $point) {
        if ($point['focus_time_minutes'] > 0) {
            // Overall correlation
            $all_focus_times[] = $point['focus_time_minutes'];
            $all_scores[] = $point['quiz_score'];
            
            // Gender-specific correlations
            if ($point['gender'] === 'Male') {
                $male_focus_times[] = $point['focus_time_minutes'];
                $male_scores[] = $point['quiz_score'];
            } elseif ($point['gender'] === 'Female') {
                $female_focus_times[] = $point['focus_time_minutes'];
                $female_scores[] = $point['quiz_score'];
            }
        }
    }

    // Calculate correlations
    $overall_correlation = calculate_pearson($all_focus_times, $all_scores);
    $male_correlation = calculate_pearson($male_focus_times, $male_scores);
    $female_correlation = calculate_pearson($female_focus_times, $female_scores);

    // Helper function to determine correlation type
    function get_correlation_type($corr) {
        if ($corr === null) return 'insufficient_data';
        if ($corr > 0.1) return 'positive';
        if ($corr < -0.1) return 'negative';
        return 'zero';
    }

    $dashboardData['focus_score_pearson'] = [
        'overall' => $overall_correlation !== null ? round($overall_correlation, 3) : null,
        'overall_type' => get_correlation_type($overall_correlation),
        'male' => $male_correlation !== null ? round($male_correlation, 3) : null,
        'male_type' => get_correlation_type($male_correlation),
        'female' => $female_correlation !== null ? round($female_correlation, 3) : null,
        'female_type' => get_correlation_type($female_correlation),
        'overall_count' => count($all_focus_times),
        'male_count' => count($male_focus_times),
        'female_count' => count($female_focus_times)
    ];
    // The binned data is no longer needed.
    $dashboardData['focus_score_binned'] = []; // Keep key to avoid frontend errors before JS update

    // 14. Get checkpoint quiz results by gender per question (correct vs wrong answers)
    // First, get all checkpoint quiz results with user answers
    $checkpointQuizResultsQuery = "
        SELECT 
            u.gender,
            cqr.user_answers,
            cqr.checkpoint_quiz_id
        FROM checkpoint_quiz_results cqr
        INNER JOIN users u ON cqr.user_id = u.id
        WHERE u.gender IS NOT NULL 
        AND u.gender != '' 
        AND u.role = 'student'
        AND cqr.user_answers IS NOT NULL
        AND cqr.user_answers != ''
        AND JSON_VALID(cqr.user_answers) = 1
    ";
    
    $result = $conn->query($checkpointQuizResultsQuery);
    
    // Check for query errors
    if (!$result) {
        error_log("Checkpoint quiz query error: " . $conn->error);
        $dashboardData['checkpoint_quiz_results_by_gender'] = [];
    } else {
        // Structure: question_id => { question_id, question_text, question_order, male_correct, male_wrong, female_correct, female_wrong }
        $questionStats = [];
        
        if ($result && $result->num_rows > 0) {
            // Cache correct answers and question text for each quiz to avoid repeated queries
            $quizDataCache = [];
            
            while ($row = $result->fetch_assoc()) {
                $gender = $row['gender'];
                $checkpointQuizId = (int)$row['checkpoint_quiz_id'];
                $userAnswers = json_decode($row['user_answers'], true);
                
                if (!is_array($userAnswers)) {
                    continue;
                }
                
                // Get correct answers and question text for this quiz (cache to avoid repeated queries)
                if (!isset($quizDataCache[$checkpointQuizId])) {
                    $quizDataQuery = $conn->prepare("
                        SELECT id, correct_answer, question_text, question_order
                        FROM checkpoint_quiz_questions 
                        WHERE checkpoint_quiz_id = ?
                        ORDER BY question_order ASC
                    ");
                    $quizDataQuery->bind_param("i", $checkpointQuizId);
                    $quizDataQuery->execute();
                    $quizDataResult = $quizDataQuery->get_result();
                    
                    $quizData = [];
                    while ($dataRow = $quizDataResult->fetch_assoc()) {
                        $quizData[(int)$dataRow['id']] = [
                            'correct_answer' => (int)$dataRow['correct_answer'],
                            'question_text' => $dataRow['question_text'],
                            'question_order' => (int)$dataRow['question_order']
                        ];
                    }
                    $quizDataCache[$checkpointQuizId] = $quizData;
                    $quizDataQuery->close();
                }
                
                $quizData = $quizDataCache[$checkpointQuizId];
                
                // Compare user answers with correct answers
                // Note: user_answers JSON keys are question IDs (from checkpoint_quiz_questions.id)
                foreach ($userAnswers as $questionIdStr => $userAnswer) {
                    // Convert question ID to integer (handle both string and int keys from JSON)
                    $questionId = is_numeric($questionIdStr) ? (int)$questionIdStr : 0;
                    $userAnswer = is_numeric($userAnswer) ? (int)$userAnswer : 0;
                    
                    // Skip if question ID is invalid or not found in quiz data
                    if ($questionId <= 0 || !isset($quizData[$questionId])) {
                        continue;
                    }
                    
                    $isCorrect = ($userAnswer === $quizData[$questionId]['correct_answer']);
                    
                    // Initialize question stats if not exists
                    if (!isset($questionStats[$questionId])) {
                        $questionStats[$questionId] = [
                            'question_id' => $questionId,
                            'question_text' => $quizData[$questionId]['question_text'],
                            'question_order' => $quizData[$questionId]['question_order'],
                            'male_correct' => 0,
                            'male_wrong' => 0,
                            'female_correct' => 0,
                            'female_wrong' => 0
                        ];
                    }
                    
                    // Count by gender
                    if ($gender === 'Male') {
                        if ($isCorrect) {
                            $questionStats[$questionId]['male_correct']++;
                        } else {
                            $questionStats[$questionId]['male_wrong']++;
                        }
                    } elseif ($gender === 'Female') {
                        if ($isCorrect) {
                            $questionStats[$questionId]['female_correct']++;
                        } else {
                            $questionStats[$questionId]['female_wrong']++;
                        }
                    }
                }
            }
            
            // Sort by question_order and convert to array format for JSON response
            if (!empty($questionStats)) {
                usort($questionStats, function($a, $b) {
                    return ($a['question_order'] ?? 999) - ($b['question_order'] ?? 999);
                });
                $dashboardData['checkpoint_quiz_results_by_gender'] = array_values($questionStats);
            } else {
                $dashboardData['checkpoint_quiz_results_by_gender'] = [];
            }
        } else {
            // No rows found
            $dashboardData['checkpoint_quiz_results_by_gender'] = [];
        }
    }
    
    // Ensure the key exists even if no data (fallback)
    if (!isset($dashboardData['checkpoint_quiz_results_by_gender'])) {
        $dashboardData['checkpoint_quiz_results_by_gender'] = [];
    }

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
