<?php
session_start();
header('Content-Type: application/json');

// Use centralized database connection
require_once __DIR__ . '/../database/db_connection.php';
try {
    $conn = getMysqliConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Get quiz_result_id from request
$quiz_result_id = isset($_GET['quiz_result_id']) ? intval($_GET['quiz_result_id']) : 0;

if (!$quiz_result_id) {
    echo json_encode(['success' => false, 'error' => 'Missing quiz_result_id']);
    exit;
}

// Get quiz result from either quiz_results or retake_results
// First, try to find it in quiz_results, then retake_results
$query_original = "
    SELECT 
        qr.id,
        qr.quiz_id,
        qr.module_id,
        qr.score,
        qr.completion_date,
        m.title AS module_title,
        fq.title AS quiz_title,
        (SELECT COUNT(*) FROM final_quiz_questions WHERE quiz_id = qr.quiz_id) as total_questions,
        (SELECT ai_feedback 
         FROM ai_recommendations ar 
         WHERE ar.user_id = qr.user_id 
           AND ar.module_id = qr.module_id 
           AND ar.quiz_id = qr.quiz_id 
         ORDER BY ar.created_at DESC 
         LIMIT 1) AS ai_feedback,
        (SELECT created_at 
         FROM ai_recommendations ar 
         WHERE ar.user_id = qr.user_id 
           AND ar.module_id = qr.module_id 
           AND ar.quiz_id = qr.quiz_id 
         ORDER BY ar.created_at DESC 
         LIMIT 1) AS feedback_created_at,
        'original' as source
    FROM quiz_results qr
    JOIN modules m ON qr.module_id = m.id
    LEFT JOIN final_quizzes fq ON qr.quiz_id = fq.id
    WHERE qr.id = ? AND qr.user_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($query_original);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Failed to prepare query: ' . $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param("ii", $quiz_result_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// If not found in quiz_results, check retake_results
if ($result->num_rows === 0) {
    $stmt->close();
    
    $query_retake = "
        SELECT 
            rr.id,
            rr.quiz_id,
            rr.module_id,
            rr.score,
            rr.completion_date,
            m.title AS module_title,
            fq.title AS quiz_title,
            (SELECT COUNT(*) FROM final_quiz_questions WHERE quiz_id = rr.quiz_id) as total_questions,
            (SELECT ai_feedback 
             FROM ai_recommendations ar 
             WHERE ar.user_id = rr.user_id 
               AND ar.module_id = rr.module_id 
               AND ar.quiz_id = rr.quiz_id 
             ORDER BY ar.created_at DESC 
             LIMIT 1) AS ai_feedback,
            (SELECT created_at 
             FROM ai_recommendations ar 
             WHERE ar.user_id = rr.user_id 
               AND ar.module_id = rr.module_id 
               AND ar.quiz_id = rr.quiz_id 
             ORDER BY ar.created_at DESC 
             LIMIT 1) AS feedback_created_at,
            'retake' as source
        FROM retake_results rr
        JOIN modules m ON rr.module_id = m.id
        LEFT JOIN final_quizzes fq ON rr.quiz_id = fq.id
        WHERE rr.id = ? AND rr.user_id = ?
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($query_retake);
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Failed to prepare retake query: ' . $conn->error]);
        $conn->close();
        exit;
    }
    
    $stmt->bind_param("ii", $quiz_result_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Quiz result not found']);
    $stmt->close();
    $conn->close();
    exit;
}

$data = $result->fetch_assoc();
$stmt->close();

// Get wrong questions (questions where user answered incorrectly)
// For now, we'll return all questions - the frontend can determine which were wrong
$quiz_id = $data['quiz_id'];
$score = intval($data['score']);
$total_questions = intval($data['total_questions']);

$questions_query = $conn->prepare("
    SELECT 
        id,
        question_text,
        option1,
        option2,
        option3,
        option4,
        correct_answer
    FROM final_quiz_questions 
    WHERE quiz_id = ?
    ORDER BY id
");

$wrong_questions = [];
if ($questions_query) {
    $questions_query->bind_param("i", $quiz_id);
    $questions_query->execute();
    $questions_result = $questions_query->get_result();
    
    // Since we don't store individual answers, we'll assume questions beyond the score were wrong
    $question_index = 0;
    while ($row = $questions_result->fetch_assoc()) {
        $question_index++;
        // If this question is beyond the score, it was likely wrong
        // This is a simplified approach - ideally you'd store individual question answers
        if ($question_index > $score) {
            $correct_option_text = $row['option' . $row['correct_answer']] ?? '';
            $wrong_questions[] = [
                'question_text' => $row['question_text'],
                'correct_answer_text' => $correct_option_text
            ];
        }
    }
    $questions_query->close();
}

$conn->close();

echo json_encode([
    'success' => true,
    'quiz' => [
        'id' => $data['id'],
        'quiz_id' => $data['quiz_id'],
        'module_id' => $data['module_id'],
        'module_title' => $data['module_title'],
        'quiz_title' => $data['quiz_title'] ?? 'Quiz',
        'score' => $score,
        'total_questions' => $total_questions,
        'score_fraction' => "{$score}/{$total_questions}",
        'score_percentage' => $total_questions > 0 ? round(($score / $total_questions) * 100, 2) : 0,
        'completion_date' => $data['completion_date'],
        'completion_datetime' => date('M d, Y h:i A', strtotime($data['completion_date'])),
        'ai_feedback' => $data['ai_feedback'],
        'feedback_created_at' => $data['feedback_created_at'],
        'wrong_questions' => $wrong_questions
    ]
]);
?>
