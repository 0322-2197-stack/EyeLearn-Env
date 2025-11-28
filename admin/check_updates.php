<?php
header('Content-Type: application/json');

// Use centralized database connection
require_once __DIR__ . '/../database/db_connection.php';
try {
    $conn = getMysqliConnection();
} catch (Exception $e) {
    die(json_encode(['error' => "Connection failed: " . $e->getMessage()]));
}

// Query to get the most recent timestamp from key tables
// This indicates when data was last added or updated.
$latestTimestampQuery = "
    SELECT GREATEST(
        (SELECT MAX(created_at) FROM users),
        (SELECT MAX(updated_at) FROM user_progress),
        (SELECT MAX(created_at) FROM quiz_results),
        (SELECT MAX(session_end_time) FROM eye_tracking_sessions)
    ) AS last_update;
";

$result = $conn->query($latestTimestampQuery);

if ($result) {
    $row = $result->fetch_assoc();
    echo json_encode(['last_update' => $row['last_update']]);
} else {
    echo json_encode(['error' => 'Failed to query for updates.']);
}

$conn->close();
?>