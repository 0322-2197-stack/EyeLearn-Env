<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1 || $_SESSION['role'] !== 'admin') {
    header("Location: ../loginpage.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['module_part_id'])) {
    header("Location: ../Amodule.php?tab=module-parts&status=error&message=" . urlencode("Invalid request"));
    exit;
}

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
try {
    $conn = getMysqliConnection();
} catch (Exception $e) {
    header("Location: ../Amodule.php?tab=module-parts&status=error&message=" . urlencode("Database connection failed"));
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    $module_part_id = (int)$_POST['module_part_id'];
    $module_id = (int)$_POST['module_id'];
    $part_title = $conn->real_escape_string(trim($_POST['part_title']));

    // Update module part
    $stmt = $conn->prepare("UPDATE module_parts SET module_id = ?, title = ? WHERE id = ?");
    $stmt->bind_param("isi", $module_id, $part_title, $module_part_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update module part");
    }
    $stmt->close();

    // Get all existing section IDs for this module part
    $stmt = $conn->prepare("SELECT id FROM module_sections WHERE module_part_id = ?");
    $stmt->bind_param("i", $module_part_id);
    $stmt->execute();
    $existing_sections_result = $stmt->get_result();
    $existing_section_ids = [];
    while ($row = $existing_sections_result->fetch_assoc()) {
        $existing_section_ids[] = (int)$row['id'];
    }
    $stmt->close();
    
    // Process sections
    if (!empty($_POST['sections']) && is_array($_POST['sections'])) {
        // Get the current maximum section_order for this module part
        $stmt = $conn->prepare("SELECT MAX(section_order) as max_order FROM module_sections WHERE module_part_id = ?");
        $stmt->bind_param("i", $module_part_id);
        $stmt->execute();
        $order_result = $stmt->get_result();
        $order_row = $order_result->fetch_assoc();
        $current_order = $order_row['max_order'] ? (int)$order_row['max_order'] : 0;
        $stmt->close();
        
        // Track which sections are being updated/inserted
        $submitted_section_ids = [];
        
        foreach ($_POST['sections'] as $section_id => $section_data) {
            $subtitle = $conn->real_escape_string(trim($section_data['subtitle']));
            // Preserve raw HTML content - don't double-escape
            $content = $section_data['content'];
            
            // Check if this is a new section (starts with "new-")
            if (strpos($section_id, 'new-') === 0) {
                // This is a new section - INSERT it
                $current_order++;
                $has_quiz = isset($section_data['has_subquiz']) ? 1 : 0;
                
                $stmt = $conn->prepare("INSERT INTO module_sections (module_part_id, subtitle, content, section_order, has_quiz) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issii", $module_part_id, $subtitle, $content, $current_order, $has_quiz);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert new section: " . $stmt->error);
                }
                $inserted_section_id = $conn->insert_id;
                $stmt->close();
                
                // Handle quiz questions for new section if it has quiz
                if ($has_quiz && !empty($section_data['quiz_questions']) && is_array($section_data['quiz_questions'])) {
                    foreach ($section_data['quiz_questions'] as $question_data) {
                        // Validate required fields
                        $required = ['question_text', 'option1', 'option2', 'option3', 'option4', 'correct_answer'];
                        foreach ($required as $field) {
                            if (!isset($question_data[$field]) || trim($question_data[$field]) === '') {
                                throw new Exception("Missing required field for question: $field");
                            }
                        }

                        $question_text = $conn->real_escape_string(trim($question_data['question_text']));
                        $option1 = $conn->real_escape_string(trim($question_data['option1']));
                        $option2 = $conn->real_escape_string(trim($question_data['option2']));
                        $option3 = $conn->real_escape_string(trim($question_data['option3']));
                        $option4 = $conn->real_escape_string(trim($question_data['option4']));
                        $correct_answer = (int)$question_data['correct_answer'];

                        // Insert quiz question
                        $stmt = $conn->prepare("INSERT INTO section_quiz_questions (section_id, question_text, option1, option2, option3, option4, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("isssssi", 
                            $inserted_section_id,
                            $question_text, 
                            $option1, 
                            $option2, 
                            $option3, 
                            $option4, 
                            $correct_answer
                        );
                        if (!$stmt->execute()) {
                            throw new Exception("Failed to insert quiz question: " . $stmt->error);
                        }
                        $stmt->close();
                    }
                }
            } else {
                // This is an existing section - UPDATE it
                $section_id = (int)$section_id;
                $submitted_section_ids[] = $section_id; // Track this section as submitted
                
                // Update section with prepared statement to prevent SQL injection
                $stmt = $conn->prepare("UPDATE module_sections SET subtitle = ?, content = ? WHERE id = ? AND module_part_id = ?");
                $stmt->bind_param("ssii", $subtitle, $content, $section_id, $module_part_id);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update section: " . $stmt->error);
                }
                $stmt->close();

                // Handle quiz questions if section has quiz
                $has_quiz = isset($section_data['has_subquiz']) ? 1 : 0;
                
                if ($has_quiz && !empty($section_data['quiz_questions']) && is_array($section_data['quiz_questions'])) {
                    foreach ($section_data['quiz_questions'] as $question_id => $question_data) {
                        // Validate required fields
                        $required = ['question_text', 'option1', 'option2', 'option3', 'option4', 'correct_answer'];
                        foreach ($required as $field) {
                            if (!isset($question_data[$field]) || trim($question_data[$field]) === '') {
                                throw new Exception("Missing required field for question: $field");
                            }
                        }

                        $question_id = (int)$question_id;
                        $question_text = $conn->real_escape_string(trim($question_data['question_text']));
                        $option1 = $conn->real_escape_string(trim($question_data['option1']));
                        $option2 = $conn->real_escape_string(trim($question_data['option2']));
                        $option3 = $conn->real_escape_string(trim($question_data['option3']));
                        $option4 = $conn->real_escape_string(trim($question_data['option4']));
                        $correct_answer = (int)$question_data['correct_answer'];

                        // Update quiz question
                        $stmt = $conn->prepare("UPDATE section_quiz_questions SET 
                            question_text = ?, 
                            option1 = ?, 
                            option2 = ?, 
                            option3 = ?, 
                            option4 = ?, 
                            correct_answer = ? 
                            WHERE id = ? AND section_id = ?");
                        $stmt->bind_param("ssssssii", 
                            $question_text, 
                            $option1, 
                            $option2, 
                            $option3, 
                            $option4, 
                            $correct_answer, 
                            $question_id, 
                            $section_id
                        );
                        if (!$stmt->execute()) {
                            throw new Exception("Failed to update quiz question: " . $stmt->error);
                        }
                        $stmt->close();
                    }
                }
            }
        }
        
        // Find sections that were removed (exist in DB but not in submitted data)
        $sections_to_delete = array_diff($existing_section_ids, $submitted_section_ids);
        
        // Delete removed sections and their associated quiz questions
        foreach ($sections_to_delete as $section_id_to_delete) {
            // First, delete quiz questions for this section
            $stmt = $conn->prepare("DELETE FROM section_quiz_questions WHERE section_id = ?");
            $stmt->bind_param("i", $section_id_to_delete);
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete quiz questions for section: " . $stmt->error);
            }
            $stmt->close();
            
            // Then delete the section itself
            $stmt = $conn->prepare("DELETE FROM module_sections WHERE id = ? AND module_part_id = ?");
            $stmt->bind_param("ii", $section_id_to_delete, $module_part_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete section: " . $stmt->error);
            }
            $stmt->close();
        }
    } else {
        // If no sections are submitted, delete all existing sections
        foreach ($existing_section_ids as $section_id_to_delete) {
            // First, delete quiz questions for this section
            $stmt = $conn->prepare("DELETE FROM section_quiz_questions WHERE section_id = ?");
            $stmt->bind_param("i", $section_id_to_delete);
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete quiz questions for section: " . $stmt->error);
            }
            $stmt->close();
            
            // Then delete the section itself
            $stmt = $conn->prepare("DELETE FROM module_sections WHERE id = ? AND module_part_id = ?");
            $stmt->bind_param("ii", $section_id_to_delete, $module_part_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete section: " . $stmt->error);
            }
            $stmt->close();
        }
    }

    // Commit transaction
    $conn->commit();
    header("Location: ../Amodule.php?tab=module-parts&status=success&message=" . urlencode("Module part updated successfully"));
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    header("Location: ../Amodule.php?tab=module-parts&status=error&message=" . urlencode($e->getMessage()));
} finally {
    $conn->close();
}
?>