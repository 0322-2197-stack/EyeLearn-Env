<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1 || $_SESSION['role'] !== 'admin') {
    header("Location: ../loginpage.php");
    exit;
}

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
$conn = getMysqliConnection();

// Create checkpoint_quizzes table if it doesn't exist
// First, drop the old table if it exists with section_id (for migration)
$conn->query("DROP TABLE IF EXISTS checkpoint_quiz_questions");
$conn->query("DROP TABLE IF EXISTS checkpoint_quizzes");

$conn->query("CREATE TABLE IF NOT EXISTS checkpoint_quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_part_id INT NOT NULL,
    quiz_title VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_part_id) REFERENCES module_parts(id) ON DELETE CASCADE,
    INDEX idx_module_part_id (module_part_id),
    UNIQUE KEY unique_part_quiz (module_part_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Create checkpoint_quiz_questions table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS checkpoint_quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    checkpoint_quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option1 VARCHAR(255) NOT NULL,
    option2 VARCHAR(255) NOT NULL,
    option3 VARCHAR(255) NOT NULL,
    option4 VARCHAR(255) NOT NULL,
    correct_answer INT NOT NULL,
    question_order INT NOT NULL,
    FOREIGN KEY (checkpoint_quiz_id) REFERENCES checkpoint_quizzes(id) ON DELETE CASCADE,
    INDEX idx_checkpoint_quiz_id (checkpoint_quiz_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required = ['module_part_id', 'quiz_title'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            header("Location: ../Amodule.php?tab=module-parts&status=error&message=" . urlencode("Missing required field: $field"));
            exit;
        }
    }

    $module_part_id = (int)$_POST['module_part_id'];
    $quiz_title = trim($_POST['quiz_title']);
    
    // Verify module part exists
    $verify_stmt = $conn->prepare("SELECT id FROM module_parts WHERE id = ?");
    $verify_stmt->bind_param("i", $module_part_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        $verify_stmt->close();
        header("Location: ../Amodule.php?tab=module-parts&status=error&message=" . urlencode("Invalid module part selected"));
        exit;
    }
    $verify_stmt->close();
    
    // Check if checkpoint quiz already exists for this module part
    $check_stmt = $conn->prepare("SELECT id FROM checkpoint_quizzes WHERE module_part_id = ?");
    $check_stmt->bind_param("i", $module_part_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $check_stmt->close();
        header("Location: ../Amodule.php?tab=module-parts&status=error&message=" . urlencode("A checkpoint quiz already exists for this module part. Please delete it first or edit it."));
        exit;
    }
    $check_stmt->close();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert the checkpoint quiz
        $stmt = $conn->prepare("INSERT INTO checkpoint_quizzes (module_part_id, quiz_title) VALUES (?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("is", $module_part_id, $quiz_title);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $checkpoint_quiz_id = $conn->insert_id;
        $stmt->close();
        
        // Process questions if they exist
        if (!empty($_POST['questions']) && is_array($_POST['questions'])) {
            $question_order = 1;
            foreach ($_POST['questions'] as $question_data) {
                // Validate question data
                $required_fields = ['question_text', 'option1', 'option2', 'option3', 'option4', 'correct_answer'];
                foreach ($required_fields as $field) {
                    if (empty($question_data[$field])) {
                        throw new Exception("Question is missing required field: $field");
                    }
                }
                
                // Insert the quiz question
                $stmt = $conn->prepare("INSERT INTO checkpoint_quiz_questions 
                    (checkpoint_quiz_id, question_text, option1, option2, option3, option4, correct_answer, question_order) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                // Store values in variables for bind_param (requires variables by reference)
                $question_text = trim($question_data['question_text']);
                $option1 = trim($question_data['option1']);
                $option2 = trim($question_data['option2']);
                $option3 = trim($question_data['option3']);
                $option4 = trim($question_data['option4']);
                $correct_answer = (int)$question_data['correct_answer'];
                
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
                $question_order++;
            }
        } else {
            throw new Exception("At least one question is required");
        }
        
        $conn->commit();
        header("Location: ../Amodule.php?tab=module-parts&status=success&message=Checkpoint+quiz+added+successfully");
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Checkpoint Quiz Addition Error: " . $e->getMessage());
        header("Location: ../Amodule.php?tab=module-parts&status=error&message=" . urlencode("Error: " . $e->getMessage()));
        exit;
    }
}

$conn->close();
?>

