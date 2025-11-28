<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1 || $_SESSION['role'] !== 'admin') {
    header("Location: ../loginpage.php");
    exit;
}

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
$conn = getMysqliConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required = ['checkpoint_quiz_id', 'module_part_id', 'quiz_title'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            header("Location: ../Amodule.php?tab=module-parts&status=error&message=" . urlencode("Missing required field: $field"));
            exit;
        }
    }

    $checkpoint_quiz_id = (int)$_POST['checkpoint_quiz_id'];
    $module_part_id = (int)$_POST['module_part_id'];
    $quiz_title = trim($_POST['quiz_title']);
    
    // Verify checkpoint quiz exists
    $verify_stmt = $conn->prepare("SELECT id FROM checkpoint_quizzes WHERE id = ? AND module_part_id = ?");
    $verify_stmt->bind_param("ii", $checkpoint_quiz_id, $module_part_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        $verify_stmt->close();
        header("Location: ../Amodule.php?tab=module-parts&status=error&message=" . urlencode("Invalid checkpoint quiz"));
        exit;
    }
    $verify_stmt->close();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update the checkpoint quiz title
        $stmt = $conn->prepare("UPDATE checkpoint_quizzes SET quiz_title = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("si", $quiz_title, $checkpoint_quiz_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();
        
        // Get existing question IDs
        $existing_questions_stmt = $conn->prepare("SELECT id FROM checkpoint_quiz_questions WHERE checkpoint_quiz_id = ?");
        $existing_questions_stmt->bind_param("i", $checkpoint_quiz_id);
        $existing_questions_stmt->execute();
        $existing_questions_result = $existing_questions_stmt->get_result();
        $existing_question_ids = [];
        while ($row = $existing_questions_result->fetch_assoc()) {
            $existing_question_ids[] = $row['id'];
        }
        $existing_questions_stmt->close();
        
        // Process questions
        if (!empty($_POST['questions']) && is_array($_POST['questions'])) {
            $question_order = 1;
            $submitted_question_ids = [];
            
            foreach ($_POST['questions'] as $question_data) {
                // Validate question data
                $required_fields = ['question_text', 'option1', 'option2', 'option3', 'option4', 'correct_answer'];
                foreach ($required_fields as $field) {
                    if (empty($question_data[$field])) {
                        throw new Exception("Question is missing required field: $field");
                    }
                }
                
                $question_id = !empty($question_data['id']) ? (int)$question_data['id'] : 0;
                
                // Store values in variables for bind_param
                $question_text = trim($question_data['question_text']);
                $option1 = trim($question_data['option1']);
                $option2 = trim($question_data['option2']);
                $option3 = trim($question_data['option3']);
                $option4 = trim($question_data['option4']);
                $correct_answer = (int)$question_data['correct_answer'];
                
                if ($question_id > 0 && in_array($question_id, $existing_question_ids)) {
                    // Update existing question
                    $stmt = $conn->prepare("UPDATE checkpoint_quiz_questions 
                        SET question_text = ?, option1 = ?, option2 = ?, option3 = ?, option4 = ?, correct_answer = ?, question_order = ?
                        WHERE id = ?");
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt->bind_param("sssssiii", 
                        $question_text,
                        $option1,
                        $option2,
                        $option3,
                        $option4,
                        $correct_answer,
                        $question_order,
                        $question_id
                    );
                    if (!$stmt->execute()) {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }
                    $stmt->close();
                    $submitted_question_ids[] = $question_id;
                } else {
                    // Insert new question
                    $stmt = $conn->prepare("INSERT INTO checkpoint_quiz_questions 
                        (checkpoint_quiz_id, question_text, option1, option2, option3, option4, correct_answer, question_order) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt->bind_param("isssssii", 
                        $checkpoint_quiz_id,
                        $question_text,
                        $option1,
                        $option2,
                        $option3,
                        $option4,
                        $correct_answer,
                        $question_order
                    );
                    if (!$stmt->execute()) {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }
                    $stmt->close();
                }
                $question_order++;
            }
            
            // Delete questions that were removed
            $questions_to_delete = array_diff($existing_question_ids, $submitted_question_ids);
            if (!empty($questions_to_delete)) {
                $placeholders = implode(',', array_fill(0, count($questions_to_delete), '?'));
                $delete_stmt = $conn->prepare("DELETE FROM checkpoint_quiz_questions WHERE id IN ($placeholders)");
                $delete_stmt->bind_param(str_repeat('i', count($questions_to_delete)), ...$questions_to_delete);
                $delete_stmt->execute();
                $delete_stmt->close();
            }
        } else {
            throw new Exception("At least one question is required");
        }
        
        $conn->commit();
        header("Location: ../Amodule.php?tab=module-parts&status=success&message=Checkpoint+quiz+updated+successfully");
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Checkpoint Quiz Edit Error: " . $e->getMessage());
        header("Location: ../Amodule.php?tab=module-parts&status=error&message=" . urlencode("Error: " . $e->getMessage()));
        exit;
    }
}

$conn->close();
?>









