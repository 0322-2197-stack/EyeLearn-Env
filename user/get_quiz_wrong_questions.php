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

// Get quiz_id from request
$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;

if (!$quiz_id) {
    echo json_encode(['success' => false, 'error' => 'Missing quiz_id']);
    exit;
}

// Fetch wrong questions from final_quiz_questions
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

$q2->close();
$conn->close();

echo json_encode([
    'success' => true,
    'wrong_questions' => $wrong_questions
]);
?>











