<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1 || $_SESSION['role'] !== 'admin') {
    header("Location: ../loginpage.php");
    exit;
}

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
$conn = getMysqliConnection();

// Process form submission for adding a new module part
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['module_id'])) {
    // Validate required fields
    $required = ['module_id', 'part_title'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            header("Location: ../Amodule.php?tab=module-parts&status=error&message=" . urlencode("Missing required field: $field"));
            exit;
        }
    }

    // Get basic module part information
    $module_id = (int)$_POST['module_id'];
    $part_title = trim($_POST['part_title']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert the module part
        $stmt = $conn->prepare("INSERT INTO module_parts (module_id, title, created_at) VALUES (?, ?, NOW())");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("is", $module_id, $part_title);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $module_part_id = $conn->insert_id;
        $stmt->close();
        
        // Process sections if they exist
        if (!empty($_POST['sections']) && is_array($_POST['sections'])) {
            foreach ($_POST['sections'] as $section_num => $section_data) {
                // Validate section data
                if (empty($section_data['subtitle']) || empty($section_data['content'])) {
                    throw new Exception("Section $section_num is missing subtitle or content");
                }
        
                $subtitle = trim($section_data['subtitle']);
                $content = trim($section_data['content']);
                $has_subquiz = isset($section_data['has_subquiz']) ? 1 : 0;
        
                // Insert the section
                $stmt = $conn->prepare("INSERT INTO module_sections 
                    (module_part_id, subtitle, content, section_order, has_quiz) 
                    VALUES (?, ?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("issii", $module_part_id, $subtitle, $content, $section_num, $has_subquiz);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
        
                $section_id = $conn->insert_id; // Get the inserted section ID
                $stmt->close();
        
                // Process quiz questions if they exist
                if ($has_subquiz && !empty($section_data['quiz_questions']) && is_array($section_data['quiz_questions'])) {
                    foreach ($section_data['quiz_questions'] as $question_num => $question_data) {
                        // Validate question data
                        $required_fields = ['question_text', 'option1', 'option2', 'option3', 'option4', 'correct_answer'];
                        foreach ($required_fields as $field) {
                            if (empty($question_data[$field])) {
                                throw new Exception("Question $question_num in section $section_num is missing $field");
                            }
                        }
        
                        // Insert the quiz question
                        $stmt = $conn->prepare("INSERT INTO section_quiz_questions 
                        (section_id, question_text, option1, option2, option3, option4, correct_answer, question_order) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        if (!$stmt) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }
                        $stmt->bind_param("issssssi", 
                        $section_id, 
                        trim($question_data['question_text']),
                        trim($question_data['option1']),
                        trim($question_data['option2']),
                        trim($question_data['option3']),
                        trim($question_data['option4']),
                        $question_data['correct_answer'], // No casting to int since it's a char
                        $question_num
                    );
                        if (!$stmt->execute()) {
                            throw new Exception("Execute failed: " . $stmt->error);
                        }
                        $stmt->close();
                    }
                }
            }
        }
        
        $conn->commit();
        header("Location: ../Amodule.php?tab=module-parts&status=success&message=Module+part+added+successfully");
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Module Part Addition Error: " . $e->getMessage());
        header("Location: ../Amodule.php?tab=module-parts&status=error&message=" . urlencode("Error: " . $e->getMessage()));
        exit;
    }
}

// Handle module addition if that form was submitted
if (isset($_POST['add_module'])) {
    // Your existing code for adding modules
}

$conn->close();
?>