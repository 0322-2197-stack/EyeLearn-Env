<?php
/**
 * Test script to verify checkpoint quiz data query
 * Access via: http://localhost/capstone/admin/test_checkpoint_data.php
 */

// Use centralized database connection
require_once __DIR__ . '/../database/db_connection.php';

header('Content-Type: application/json');

try {
    $conn = getMysqliConnection();
    
    // Test the query
    $checkpointQuizResultsQuery = "
        SELECT 
            u.id as user_id,
            u.gender,
            u.first_name,
            u.last_name,
            cqr.user_answers,
            cqr.checkpoint_quiz_id,
            cqr.score,
            cqr.total_questions
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
    
    $testData = [
        'query_success' => $result !== false,
        'num_rows' => $result ? $result->num_rows : 0,
        'error' => $result ? null : $conn->error,
        'results' => []
    ];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $userAnswers = json_decode($row['user_answers'], true);
            $testData['results'][] = [
                'user_id' => $row['user_id'],
                'name' => $row['first_name'] . ' ' . $row['last_name'],
                'gender' => $row['gender'],
                'checkpoint_quiz_id' => $row['checkpoint_quiz_id'],
                'score' => $row['score'],
                'total_questions' => $row['total_questions'],
                'user_answers_decoded' => $userAnswers,
                'user_answers_is_array' => is_array($userAnswers),
                'user_answers_count' => is_array($userAnswers) ? count($userAnswers) : 0
            ];
        }
    }
    
    // Also test the questions query
    $questionsQuery = "
        SELECT id, checkpoint_quiz_id, correct_answer, question_text, question_order
        FROM checkpoint_quiz_questions 
        WHERE checkpoint_quiz_id = 1
        ORDER BY question_order ASC
    ";
    
    $questionsResult = $conn->query($questionsQuery);
    $testData['questions'] = [];
    
    if ($questionsResult && $questionsResult->num_rows > 0) {
        while ($qRow = $questionsResult->fetch_assoc()) {
            $testData['questions'][] = [
                'id' => $qRow['id'],
                'checkpoint_quiz_id' => $qRow['checkpoint_quiz_id'],
                'correct_answer' => $qRow['correct_answer'],
                'question_order' => $qRow['question_order'],
                'question_text' => substr($qRow['question_text'], 0, 50) . '...'
            ];
        }
    }
    
    echo json_encode($testData, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
}
?>

