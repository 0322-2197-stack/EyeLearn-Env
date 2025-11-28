<?php
session_start();
header('Content-Type: application/json');

// Connect to DB
require_once __DIR__ . '/../database/db_connection.php';
$conn = getMysqliConnection();
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Get the most recent quiz with score < 70
$q = $conn->prepare("SELECT * FROM quiz_results WHERE user_id = ? AND score < 70 ORDER BY completion_date DESC LIMIT 1");
$q->bind_param("i", $user_id);
$q->execute();
$r = $q->get_result();

if (!$r || $r->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'No low-score quiz found']);
    exit;
}

$quiz = $r->fetch_assoc();
$quiz_id = $quiz['quiz_id'];
$module_id = $quiz['module_id'];
$score = intval($quiz['score']);

// Fetch wrong questions (for now: all questions from final_quiz_questions)
$q2 = $conn->prepare("SELECT question_text, option1, option2, option3, option4, correct_answer 
                      FROM final_quiz_questions WHERE quiz_id = ?");
$q2->bind_param("i", $quiz_id);
$q2->execute();
$res2 = $q2->get_result();

$wrong_questions = [];
while ($row = $res2->fetch_assoc()) {
    $correctOption = $row['option' . $row['correct_answer']] ?? null;
    $wrong_questions[] = [
        'question_text' => $row['question_text'],
        'correct_answer_text' => $correctOption
    ];
}

$conn->close();

// Return as structured JSON
echo json_encode([
    'success' => true,
    'quiz' => [
        'user_id' => $user_id,
        'module_id' => $module_id,
        'quiz_id' => $quiz_id,
        'score' => $score,
        'wrong_questions' => $wrong_questions,
        'module_title' => 'Latest Module'
    ]
]);
?>
