<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../loginpage.php");
    exit;
}

if (!function_exists('ensureFinalQuizRetakeColumn')) {
    function ensureFinalQuizRetakeColumn(mysqli $connection): void
    {
        $columnResult = $connection->query("SHOW COLUMNS FROM final_quizzes LIKE 'allow_retake'");
        if ($columnResult && $columnResult->num_rows === 0) {
            $connection->query("ALTER TABLE final_quizzes ADD COLUMN allow_retake TINYINT(1) NOT NULL DEFAULT 0");
        }

        if ($columnResult instanceof mysqli_result) {
            $columnResult->free();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['module_id'], $_POST['final_quiz_id'], $_POST['enable'])
) {
    $moduleId = intval($_POST['module_id']);
    $finalQuizId = intval($_POST['final_quiz_id']);
    $enableRetake = $_POST['enable'] === '1' ? 1 : 0;

    // Use centralized database connection
    require_once __DIR__ . '/../../database/db_connection.php';
    try {
        $conn = getMysqliConnection();
    } catch (Exception $e) {
        header("Location: ../Amodule.php?retake=error");
        exit;
    }

    ensureFinalQuizRetakeColumn($conn);

    $stmt = $conn->prepare("UPDATE final_quizzes SET allow_retake = ? WHERE id = ? AND module_id = ?");
    if ($stmt) {
        $stmt->bind_param("iii", $enableRetake, $finalQuizId, $moduleId);
        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            $status = $enableRetake ? 'enabled' : 'disabled';
            $stmt->close();
            $conn->close();
            header("Location: ../Amodule.php?retake={$status}");
            exit;
        }
        $stmt->close();
    }

    $conn->close();
    header("Location: ../Amodule.php?retake=error");
    exit;
}

header("Location: ../Amodule.php?retake=error");
exit;

