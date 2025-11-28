<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1 || $_SESSION['role'] !== 'admin') {
    header("Location: ../../loginpage.php");
    exit;
}
// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
$conn = getMysqliConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['module_id'])) {
    $module_id = intval($_POST['module_id']);
    
    try {
        // Start transaction
        $conn->begin_transaction();

        // Delete related module parts first
        $stmt = $conn->prepare("DELETE FROM module_parts WHERE module_id = ?");
        $stmt->bind_param("i", $module_id);
        $stmt->execute();
        
        // Delete the module
        $stmt = $conn->prepare("DELETE FROM modules WHERE id = ?");
        $stmt->bind_param("i", $module_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Redirect back with success message
        header("Location: ../Amodule.php?status=success&message=Module deleted successfully");
        exit;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        header("Location: ../Amodule.php?status=error&message=Failed to delete module: " . $e->getMessage());
        exit;
    }
} else {
    header("Location: ../Amodule.php?status=error&message=Invalid request");
    exit;
}