<?php
session_start();
header('Content-Type: application/json');

// Verify user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['quiz_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing quiz_id']);
    exit;
}

$quiz_id = (int)$_GET['quiz_id'];

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
try {
    $conn = getMysqliConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

try {
    // Fetch questions for this checkpoint quiz
    $stmt = $conn->prepare("SELECT id, question_text, option1, option2, option3, option4, correct_answer 
                           FROM checkpoint_quiz_questions 
                           WHERE checkpoint_quiz_id = ? 
                           ORDER BY question_order ASC");
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $questions = [];
    while ($row = $result->fetch_assoc()) {
        $questions[] = [
            'id' => (int)$row['id'],
            'question_text' => htmlspecialchars($row['question_text'], ENT_QUOTES, 'UTF-8'),
            'option1' => htmlspecialchars($row['option1'], ENT_QUOTES, 'UTF-8'),
            'option2' => htmlspecialchars($row['option2'], ENT_QUOTES, 'UTF-8'),
            'option3' => htmlspecialchars($row['option3'], ENT_QUOTES, 'UTF-8'),
            'option4' => htmlspecialchars($row['option4'], ENT_QUOTES, 'UTF-8'),
            'correct_answer' => (int)$row['correct_answer']
        ];
    }
    $stmt->close();
    
    echo json_encode($questions);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>









