<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1 || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid module part ID']);
    exit;
}

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
$conn = getMysqliConnection();
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

$part_id = (int) $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM module_parts WHERE id = ?");
$stmt->bind_param("i", $part_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $module_part = $result->fetch_assoc();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'module_part' => $module_part]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Module part not found']);
}

$stmt->close();
$conn->close();
?>