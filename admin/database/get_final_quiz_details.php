<?php
header('Content-Type: application/json');

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
try {
    $conn = getMysqliConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid quiz ID']);
    exit;
}

$quiz_id = intval($_GET['id']);

// Fetch quiz info
$quiz_result = $conn->query("SELECT id, title, module_id FROM final_quizzes WHERE id = $quiz_id");
if (!$quiz_result || $quiz_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Quiz not found']);
    exit;
}
$quiz = $quiz_result->fetch_assoc();

// Fetch questions
$questions_result = $conn->query("SELECT id, question_text, option1, option2, option3, option4, correct_answer FROM final_quiz_questions WHERE quiz_id = $quiz_id");
$questions = [];
while ($row = $questions_result->fetch_assoc()) {
    $questions[] = $row;
}

echo json_encode(['success' => true, 'quiz' => $quiz, 'questions' => $questions]);
$conn->close();
?>
