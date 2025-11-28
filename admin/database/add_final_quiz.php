<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized access');
}

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
$conn = getMysqliConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $module_id = intval($_POST['module_id']);
    $quiz_title = $conn->real_escape_string($_POST['quiz_title']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert quiz
        $stmt = $conn->prepare("INSERT INTO final_quizzes (module_id, title) VALUES (?, ?)");
        $stmt->bind_param("is", $module_id, $quiz_title);
        $stmt->execute();
        $quiz_id = $conn->insert_id;
        
        // Insert questions and options
        foreach ($_POST['questions'] as $question) {
            $q_text = $conn->real_escape_string($question['text']);
            $options = array_map(array($conn, 'real_escape_string'), $question['options']);
            $correct = intval($question['correct']);
            
            // Insert into final_quiz_questions
            $stmt = $conn->prepare("INSERT INTO final_quiz_questions 
                (quiz_id, question_text, option1, option2, option3, option4, correct_answer) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("isssssi", 
                $quiz_id, 
                $q_text,
                $options[0],
                $options[1],
                $options[2],
                $options[3],
                $correct
            );
            $stmt->execute();
        }
        
        $conn->commit();
        header("Location: ../Amodule.php?tab=final-quiz&status=success&message=Quiz created successfully");
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../Amodule.php?tab=final-quiz&status=error&message=Error creating quiz: " . $e->getMessage());
        exit;
    }
}

$conn->close();