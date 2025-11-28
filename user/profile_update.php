<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Use centralized database connection
require_once __DIR__ . '/../database/db_connection.php';
try {
    $conn = getMysqliConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $section = trim($_POST['section'] ?? '');

    if (empty($first_name) || empty($last_name)) {
        echo json_encode(['success' => false, 'message' => 'Name fields cannot be empty']);
        exit();
    }

    // Check if section column exists, if not, update without it
    $check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'section'");
    if ($check_column && $check_column->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, gender = ?, section = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $first_name, $last_name, $gender, $section, $user_id);
    } else {
        // Section column doesn't exist, update without it
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, gender = ? WHERE id = ?");
        $stmt->bind_param("sssi", $first_name, $last_name, $gender, $user_id);
    }

    if ($stmt->execute()) {
        // Update session variables
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        
        $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'gender' => $gender,
                'section' => $section,
                'initials' => $initials,
                'full_name' => $first_name . ' ' . $last_name
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
$conn->close();
