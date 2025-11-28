<?php
// admin/database/get_quiz_history.php - Get quiz history for a student
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if (!$student_id) {
    echo json_encode(['success' => false, 'error' => 'Student ID required']);
    exit();
}

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
try {
    $conn = getMysqliConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

try {
    // Check if retake_results table exists
    $tablesResult = $conn->query("SHOW TABLES LIKE 'retake_results'");
    $hasRetakeTable = $tablesResult->num_rows > 0;
    
    // Get quiz history from both quiz_results and retake_results (if exists)
    if ($hasRetakeTable) {
        $quiz_history_query = "
            SELECT 
                m.title AS module_name,
                m.id AS module_id,
                qr.quiz_id,
                qr.completion_date,
                qr.score,
                'original' AS attempt_type,
                1 AS attempt_number,
                COALESCE((
                    SELECT COUNT(fqq.id)
                    FROM final_quiz_questions fqq
                    WHERE fqq.quiz_id = qr.quiz_id
                ), 0) AS total_questions
            FROM quiz_results qr
            JOIN modules m ON qr.module_id = m.id
            WHERE qr.user_id = ?
            
            UNION ALL
            
            SELECT 
                m.title AS module_name,
                m.id AS module_id,
                rr.quiz_id,
                rr.completion_date,
                rr.score,
                'retake' AS attempt_type,
                (SELECT COUNT(*) + 1 
                 FROM retake_results rr2 
                 WHERE rr2.user_id = rr.user_id 
                 AND rr2.quiz_id = rr.quiz_id 
                 AND rr2.completion_date <= rr.completion_date) AS attempt_number,
                COALESCE((
                    SELECT COUNT(fqq.id)
                    FROM final_quiz_questions fqq
                    WHERE fqq.quiz_id = rr.quiz_id
                ), 0) AS total_questions
            FROM retake_results rr
            JOIN modules m ON rr.module_id = m.id
            WHERE rr.user_id = ?
            
            ORDER BY completion_date DESC;
        ";
        
        $stmt = $conn->prepare($quiz_history_query);
        $stmt->bind_param('ii', $student_id, $student_id);
    } else {
        $quiz_history_query = "
            SELECT 
                m.title AS module_name,
                m.id AS module_id,
                qr.quiz_id,
                qr.completion_date,
                qr.score,
                'original' AS attempt_type,
                1 AS attempt_number,
                COALESCE((
                    SELECT COUNT(fqq.id)
                    FROM final_quiz_questions fqq
                    WHERE fqq.quiz_id = qr.quiz_id
                ), 0) AS total_questions
            FROM quiz_results qr
            JOIN modules m ON qr.module_id = m.id
            WHERE qr.user_id = ?
            ORDER BY completion_date DESC;
        ";
        
        $stmt = $conn->prepare($quiz_history_query);
        $stmt->bind_param('i', $student_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $quiz_history = [];
    $module_attempts = []; // Track attempts per module/quiz
    
    // First, collect all rows and order by completion_date ASC to count attempts correctly
    $all_rows = [];
    while ($row = $result->fetch_assoc()) {
        $all_rows[] = $row;
    }
    
    // Sort by module_id, quiz_id, completion_date ASC to count attempts in chronological order
    usort($all_rows, function($a, $b) {
        if ($a['module_id'] != $b['module_id']) {
            return $a['module_id'] - $b['module_id'];
        }
        if ($a['quiz_id'] != $b['quiz_id']) {
            return $a['quiz_id'] - $b['quiz_id'];
        }
        return strtotime($a['completion_date']) - strtotime($b['completion_date']);
    });
    
    // Count attempts in chronological order
    foreach ($all_rows as $row) {
        $module_quiz_key = $row['module_id'] . '_' . $row['quiz_id'];
        
        // Initialize or increment attempt counter
        if (!isset($module_attempts[$module_quiz_key])) {
            $module_attempts[$module_quiz_key] = 0;
        }
        $module_attempts[$module_quiz_key]++;
        $attempt_number = $module_attempts[$module_quiz_key];
        
        // Calculate percentage
        $percentage = 0;
        if ($row['total_questions'] > 0) {
            $percentage = round(($row['score'] / $row['total_questions']) * 100, 1);
        }
        
        // Determine status - only Passed or Failed (70% threshold)
        $status = $row['total_questions'] > 0 && $percentage >= 70 ? 'Passed' : 'Failed';
        
        // Convert to ordinal (1st, 2nd, 3rd, etc.)
        $suffix = 'th';
        if ($attempt_number % 100 >= 11 && $attempt_number % 100 <= 13) {
            $suffix = 'th';
        } else {
            switch ($attempt_number % 10) {
                case 1:
                    $suffix = 'st';
                    break;
                case 2:
                    $suffix = 'nd';
                    break;
                case 3:
                    $suffix = 'rd';
                    break;
            }
        }
        $attempt_label = $attempt_number . $suffix;
        
        $quiz_history[] = [
            'module_name' => $row['module_name'],
            'module_id' => (int)$row['module_id'],
            'quiz_id' => (int)$row['quiz_id'],
            'completion_date' => $row['completion_date'],
            'score' => (int)$row['score'],
            'total_questions' => (int)$row['total_questions'],
            'percentage' => $percentage,
            'attempts' => $attempt_number,
            'attempt_label' => $attempt_label,
            'status' => $status,
            'attempt_type' => $row['attempt_type']
        ];
    }
    
    // Reverse order to show newest first
    $quiz_history = array_reverse($quiz_history);
    
    $response = [
        'success' => true,
        'quiz_history' => $quiz_history
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>

