<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit();
}

// Use centralized database connection
require_once __DIR__ . '/../../database/db_connection.php';
try {
    $conn = getMysqliConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

$user_id = $_SESSION['user_id'];
$module_id = intval($_GET['module_id'] ?? 0);
$section_id = intval($_GET['section_id'] ?? 0);

if ($module_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid module ID']);
    exit();
}

try {
    $query = "SELECT 
                SUM(total_time_seconds) as total_time,
                COUNT(*) as session_count,
                MAX(last_updated) as last_session
              FROM eye_tracking_sessions 
              WHERE user_id = ? AND module_id = ?";
    
    $params = [$user_id, $module_id];
    $types = "ii";
    
    if ($section_id > 0) {
        $query .= " AND section_id = ?";
        $params[] = $section_id;
        $types .= "i";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'total_time' => intval($data['total_time'] ?? 0),
            'session_count' => intval($data['session_count']),
            'last_session' => $data['last_session'],
            'formatted_time' => formatTime(intval($data['total_time'] ?? 0))
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'total_time' => 0,
            'session_count' => 0,
            'last_session' => null,
            'formatted_time' => '00:00:00'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

function formatTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}

$conn->close();
?>
