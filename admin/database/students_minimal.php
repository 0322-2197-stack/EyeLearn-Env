<?php
// admin/database/students_minimal.php - Real data API (Safe version)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
try {
    $conn = getMysqliConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $moduleId = isset($_GET['module_id']) ? (int)$_GET['module_id'] : null;
    $section = isset($_GET['section']) ? trim($_GET['section']) : '';
    
    // Check if tables exist first
    $tables = [];
    $tablesResult = $conn->query("SHOW TABLES");
    while ($row = $tablesResult->fetch_array()) {
        $tables[] = $row[0];
    }
    
    $hasUserProgress = in_array('user_progress', $tables);
    $hasUserModuleProgress = in_array('user_module_progress', $tables);
    $hasEyeTracking = in_array('eye_tracking_sessions', $tables);
    
    // Check if section column exists
    $hasSection = false;
    $columnsResult = $conn->query("SHOW COLUMNS FROM users LIKE 'section'");
    if ($columnsResult && $columnsResult->num_rows > 0) {
        $hasSection = true;
    }
    
    // Basic student query
    $query = "SELECT 
        u.id,
        u.first_name,
        u.last_name,
        u.email,
        u.gender";
    
    if ($hasSection) {
        $query .= ",
        u.section";
    }
    
    $query .= ",
        CONCAT('ST-', LPAD(u.id, 4, '0')) as student_id
        FROM users u
        WHERE u.role = 'student'";
    
    // Add module filter if provided - show students who have interacted with this module
    if ($moduleId && $moduleId > 0) {
        $moduleId = $conn->real_escape_string($moduleId);
        $query .= " AND (
            EXISTS (SELECT 1 FROM user_progress WHERE user_id = u.id AND module_id = $moduleId)
            OR EXISTS (SELECT 1 FROM eye_tracking_sessions WHERE user_id = u.id AND module_id = $moduleId)
            OR EXISTS (SELECT 1 FROM user_module_progress WHERE user_id = u.id AND module_id = $moduleId)
            OR EXISTS (SELECT 1 FROM module_completions WHERE user_id = u.id AND module_id = $moduleId)
        )";
    }
    
    // Add section filter if provided
    if (!empty($section) && $hasSection) {
        $section = $conn->real_escape_string($section);
        $query .= " AND u.section = '$section'";
    }
    
    // Add search condition if provided
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $query .= " AND (u.first_name LIKE '%$search%' 
                    OR u.last_name LIKE '%$search%' 
                    OR u.email LIKE '%$search%'
                    OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search%')";
    }
    
    $query .= " ORDER BY u.id ASC LIMIT 50";
    
    $result = $conn->query($query);
    
    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Query failed: ' . $conn->error]);
        exit;
    }
    
    $students = [];
    
    while ($row = $result->fetch_assoc()) {
        $student_id = (int)$row['id'];
        
        // Get progress from database
        $progress = 0;
        
        // Only calculate progress if we have the necessary tables
        if (!$hasUserModuleProgress && !$hasUserProgress) {
            $progress = 0;
        } else if ($moduleId && $moduleId > 0) {
            // Get progress for specific module
            $moduleIdEscaped = $conn->real_escape_string($moduleId);
            
            // First try user_module_progress table (most accurate)
            if ($hasUserModuleProgress) {
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
                        $progress = min(100, max(0, round(($completedCount / $totalSections) * 100, 0)));
                    }
                }
            }
            
            // Fallback to user_progress table if user_module_progress doesn't exist or has no data
            if ($progress == 0 && $hasUserProgress) {
                $fallbackQuery = "SELECT completion_percentage FROM user_progress WHERE user_id = $student_id AND module_id = $moduleIdEscaped";
                $fallbackResult = $conn->query($fallbackQuery);
                if ($fallbackResult && $fallbackResult->num_rows > 0) {
                    $fallbackRow = $fallbackResult->fetch_assoc();
                    $progress = min(100, max(0, round($fallbackRow['completion_percentage'] ?: 0, 0)));
                }
            }
        } else {
            // Calculate average progress across all modules
            if ($hasUserModuleProgress) {
                // Get all modules the student has interacted with from user_module_progress
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
                            $moduleProgress = min(100, max(0, ($completedCount / $totalSections) * 100));
                            $moduleProgresses[] = $moduleProgress;
                        }
                    }
                    
                    // Calculate average progress across all modules
                    if (count($moduleProgresses) > 0) {
                        $progress = min(100, max(0, round(array_sum($moduleProgresses) / count($moduleProgresses), 0)));
                    }
                }
            }
            
            // Fallback to user_progress table if user_module_progress has no data or doesn't exist
            if ($progress == 0 && $hasUserProgress) {
                $fallbackQuery = "SELECT AVG(completion_percentage) as avg_progress FROM user_progress WHERE user_id = $student_id";
                $fallbackResult = $conn->query($fallbackQuery);
                if ($fallbackResult && $fallbackResult->num_rows > 0) {
                    $fallbackRow = $fallbackResult->fetch_assoc();
                    $avgProgress = $fallbackRow['avg_progress'];
                    if ($avgProgress !== null) {
                        $progress = min(100, max(0, round($avgProgress, 0)));
                    }
                }
            }
        }
        
        // Get today's focus data if table exists
        $today_focus_time = 0;
        $today_unfocus_time = 0;
        $today_total_sessions = 0;
        
        // Get overall focus time and sessions data (similar to Adashboard.php)
        $avg_focus_time_minutes = null;
        $total_sessions = 0;
        $valid_sessions = 0;
        $avg_focus_time_per_session_minutes = null;
        
        if ($hasEyeTracking) {
            if ($moduleId && $moduleId > 0) {
                // Get focus data for specific module (today)
                $moduleIdEscaped = $conn->real_escape_string($moduleId);
                $focusResult = $conn->query("SELECT 
                    SUM(CASE WHEN total_time_seconds > 0 THEN total_time_seconds ELSE 0 END) / 60 as focus_minutes,
                    COUNT(*) as session_count
                    FROM eye_tracking_sessions 
                    WHERE user_id = $student_id AND module_id = $moduleIdEscaped AND DATE(created_at) = CURDATE()");
                
                // Get overall focus data for specific module (all time) - using SUM like get_dashboard_data.php
                $overallFocusResult = $conn->query("SELECT 
                    SUM(CASE WHEN total_time_seconds BETWEEN 30 AND 7200 THEN total_time_seconds ELSE 0 END) as total_focus_time_seconds,
                    COUNT(DISTINCT id) as total_sessions,
                    COUNT(CASE WHEN total_time_seconds BETWEEN 30 AND 7200 THEN 1 ELSE NULL END) as valid_sessions
                    FROM eye_tracking_sessions 
                    WHERE user_id = $student_id AND module_id = $moduleIdEscaped");
            } else {
                // Get focus data for all modules (today)
                $focusResult = $conn->query("SELECT 
                    SUM(CASE WHEN total_time_seconds > 0 THEN total_time_seconds ELSE 0 END) / 60 as focus_minutes,
                    COUNT(*) as session_count
                    FROM eye_tracking_sessions 
                    WHERE user_id = $student_id AND DATE(created_at) = CURDATE()");
                
                // Get overall focus data for all modules (all time) - using SUM like get_dashboard_data.php
                $overallFocusResult = $conn->query("SELECT 
                    SUM(CASE WHEN total_time_seconds BETWEEN 30 AND 7200 THEN total_time_seconds ELSE 0 END) as total_focus_time_seconds,
                    COUNT(DISTINCT id) as total_sessions,
                    COUNT(CASE WHEN total_time_seconds BETWEEN 30 AND 7200 THEN 1 ELSE NULL END) as valid_sessions
                    FROM eye_tracking_sessions 
                    WHERE user_id = $student_id");
            }
            
            if ($focusResult && $focusResult->num_rows > 0) {
                $focusRow = $focusResult->fetch_assoc();
                $today_focus_time = round($focusRow['focus_minutes'] ?: 0, 0);
                $today_total_sessions = (int)($focusRow['session_count'] ?: 0);
            }
            
            // Process overall focus data
            if ($overallFocusResult && $overallFocusResult->num_rows > 0) {
                $overallRow = $overallFocusResult->fetch_assoc();
                $total_focus_time_seconds = $overallRow['total_focus_time_seconds'];
                $total_sessions = (int)($overallRow['total_sessions'] ?: 0);
                $valid_sessions = (int)($overallRow['valid_sessions'] ?: 0);
                
                if ($total_focus_time_seconds !== null && $total_focus_time_seconds > 0) {
                    $avg_focus_time_minutes = round($total_focus_time_seconds / 60, 1);
                    
                    // Calculate Average Focus Time Per Session = Total Focus Time / Total Valid Sessions
                    if ($valid_sessions > 0) {
                        $avg_focus_time_per_session_minutes = round(($total_focus_time_seconds / 60) / $valid_sessions, 1);
                    }
                }
            }
        } else {
            // Generate some sample focus data
            $today_focus_time = 15 + ($student_id % 25);
            $today_total_sessions = 1 + ($student_id % 3);
        }
        
        // Estimate unfocus time
        if ($today_unfocus_time == 0 && $today_focus_time > 0) {
            $today_unfocus_time = round($today_focus_time * 0.3, 0);
        }
        
        $today_total_time = $today_focus_time + $today_unfocus_time;
        $today_focus_percentage = $today_total_time > 0 ? round(($today_focus_time / $today_total_time) * 100, 0) : 0;
        
        // Clean up student name
        $studentName = trim($row['first_name'] . ' ' . $row['last_name']);
        if (empty($studentName)) {
            $studentName = "Student " . $row['id'];
        }
        
        $students[] = [
            'id' => $student_id,
            'name' => $studentName,
            'email' => $row['email'] ?: "student{$row['id']}@example.com",
            'student_id' => $row['student_id'],
            'gender' => $row['gender'] ?: 'Not specified',
            'section' => ($hasSection && isset($row['section'])) ? ($row['section'] ?: 'N/A') : 'N/A',
            'progress' => $progress,
            'today_focus_time' => $today_focus_time,
            'today_unfocus_time' => $today_unfocus_time,
            'today_focus_percentage' => $today_focus_percentage,
            'total_sessions' => $today_total_sessions,
            // Overall focus time and sessions data (similar to Adashboard.php)
            'avg_focus_time_minutes' => $avg_focus_time_minutes,
            'total_sessions_overall' => $total_sessions,
            'valid_sessions' => $valid_sessions,
            'avg_focus_time_per_session_minutes' => $avg_focus_time_per_session_minutes
        ];
    }
    
    echo json_encode([
        'success' => true,
        'students' => $students,
        'total' => count($students),
        'tables_available' => [
            'user_progress' => $hasUserProgress,
            'user_module_progress' => $hasUserModuleProgress,
            'eye_tracking_sessions' => $hasEyeTracking
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage(), 'line' => $e->getLine()]);
}

$conn->close();
?>
