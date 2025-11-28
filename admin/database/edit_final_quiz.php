<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../Amodule.php?tab=final-quiz&status=error&message=Unauthorized');
    exit;
}

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
try {
    $conn = getMysqliConnection();
} catch (Exception $e) {
    header('Location: ../Amodule.php?tab=final-quiz&status=error&message=Database+connection+failed');
    exit;
}

$quiz_id = intval($_POST['quiz_id']);
$title = trim($_POST['quiz_title']);

$conn->begin_transaction();

try {
    // Update quiz title
    $stmt = $conn->prepare("UPDATE final_quizzes SET title = ? WHERE id = ?");
    $stmt->bind_param("si", $title, $quiz_id);
    $stmt->execute();

    // Track existing question IDs to detect deletions
    $existingIds = [];
    $res = $conn->query("SELECT id FROM final_quiz_questions WHERE quiz_id = $quiz_id");
    while ($r = $res->fetch_assoc()) $existingIds[] = $r['id'];

    $postedIds = [];
    foreach ($_POST['questions'] as $q) {
        $text = $q['text'];
        $o1 = $q['option1'];
        $o2 = $q['option2'];
        $o3 = $q['option3'];
        $o4 = $q['option4'];
        $correct = intval($q['correct']);
        $qid = intval($q['id'] ?? 0);

        if ($qid > 0) {
            // Update existing question
            $stmt = $conn->prepare("
                UPDATE final_quiz_questions 
                SET question_text = ?, option1 = ?, option2 = ?, option3 = ?, option4 = ?, correct_answer = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sssssii", $text, $o1, $o2, $o3, $o4, $correct, $qid);
            $stmt->execute();
            $postedIds[] = $qid;
        } else {
            // Add new question
            $stmt = $conn->prepare("
                INSERT INTO final_quiz_questions (quiz_id, question_text, option1, option2, option3, option4, correct_answer)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssssi", $quiz_id, $text, $o1, $o2, $o3, $o4, $correct);
            $stmt->execute();
        }
    }

    // Delete removed questions
    $toDelete = array_diff($existingIds, $postedIds);
    if (!empty($toDelete)) {
        $ids = implode(',', $toDelete);
        $conn->query("DELETE FROM final_quiz_questions WHERE id IN ($ids)");
    }

    $conn->commit();
    header('Location: ../Amodule.php?tab=final-quiz&status=success&message=Quiz+updated+successfully');
} catch (Exception $e) {
    $conn->rollback();
    header('Location: ../Amodule.php?tab=final-quiz&status=error&message=' . urlencode($e->getMessage()));
}

$conn->close();
?>
