<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1 || $_SESSION['role'] !== 'admin') {
    header("Location: ../loginpage.php");
    exit;
}

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
$conn = getMysqliConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkpoint_quiz_id'])) {
    $checkpoint_quiz_id = (int)$_POST['checkpoint_quiz_id'];
    
    // Verify checkpoint quiz exists
    $verify_stmt = $conn->prepare("SELECT id FROM checkpoint_quizzes WHERE id = ?");
    $verify_stmt->bind_param("i", $checkpoint_quiz_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        $verify_stmt->close();
        header("Location: ../Amodule.php?tab=module-parts&status=error&message=" . urlencode("Checkpoint quiz not found"));
        exit;
    }
    $verify_stmt->close();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete questions first (foreign key constraint)
        $delete_questions_stmt = $conn->prepare("DELETE FROM checkpoint_quiz_questions WHERE checkpoint_quiz_id = ?");
        if (!$delete_questions_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $delete_questions_stmt->bind_param("i", $checkpoint_quiz_id);
        if (!$delete_questions_stmt->execute()) {
            throw new Exception("Execute failed: " . $delete_questions_stmt->error);
        }
        $delete_questions_stmt->close();
        
        // Delete the checkpoint quiz
        $delete_stmt = $conn->prepare("DELETE FROM checkpoint_quizzes WHERE id = ?");
        if (!$delete_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $delete_stmt->bind_param("i", $checkpoint_quiz_id);
        if (!$delete_stmt->execute()) {
            throw new Exception("Execute failed: " . $delete_stmt->error);
        }
        $delete_stmt->close();
        
        $conn->commit();
        header("Location: ../Amodule.php?tab=module-parts&status=success&message=Checkpoint+quiz+deleted+successfully");
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Checkpoint Quiz Deletion Error: " . $e->getMessage());
        header("Location: ../Amodule.php?tab=module-parts&status=error&message=" . urlencode("Error: " . $e->getMessage()));
        exit;
    }
}

$conn->close();
header("Location: ../Amodule.php?tab=module-parts&status=error&message=Invalid+request");
exit;
?>









