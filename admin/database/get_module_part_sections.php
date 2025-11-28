<?php
session_start();
header('Content-Type: application/json');

// Verify user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['part_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing part_id']);
    exit;
}

$part_id = (int)$_GET['part_id'];

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
    // Fetch sections for this module part
    $stmt = $conn->prepare("SELECT id, subtitle, content FROM module_sections WHERE module_part_id = ? ORDER BY id ASC");
    $stmt->bind_param("i", $part_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $section = [
            'id' => (int)$row['id'],
            'subtitle' => htmlspecialchars($row['subtitle'], ENT_QUOTES, 'UTF-8'),
            // Keep content raw - don't htmlspecialchars it since it contains HTML/images
            'content' => $row['content'],
            'has_subquiz' => 0,
            'quiz_questions' => []
        ];
        
        // Fetch quiz questions for this section if they exist
        $quiz_stmt = $conn->prepare("SELECT id, question_text, option1, option2, option3, option4, correct_answer FROM section_quiz_questions WHERE section_id = ? ORDER BY id ASC");
        $quiz_stmt->bind_param("i", $row['id']);
        $quiz_stmt->execute();
        $quiz_result = $quiz_stmt->get_result();
        
        if ($quiz_result->num_rows > 0) {
            $section['has_subquiz'] = 1;
            while ($quiz_row = $quiz_result->fetch_assoc()) {
                $section['quiz_questions'][] = [
                    'id' => (int)$quiz_row['id'],
                    'question_text' => htmlspecialchars($quiz_row['question_text'], ENT_QUOTES, 'UTF-8'),
                    'option1' => htmlspecialchars($quiz_row['option1'], ENT_QUOTES, 'UTF-8'),
                    'option2' => htmlspecialchars($quiz_row['option2'], ENT_QUOTES, 'UTF-8'),
                    'option3' => htmlspecialchars($quiz_row['option3'], ENT_QUOTES, 'UTF-8'),
                    'option4' => htmlspecialchars($quiz_row['option4'], ENT_QUOTES, 'UTF-8'),
                    'correct_answer' => (int)$quiz_row['correct_answer']
                ];
            }
        }
        $quiz_stmt->close();
        
        $sections[] = $section;
    }
    $stmt->close();
    
    echo json_encode($sections);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>