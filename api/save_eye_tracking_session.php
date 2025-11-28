<?php
/**
 * Save eye tracking session data to database
 */

require_once '../database/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    $user_id = $_SESSION['user_id'] ?? 1;
    $module_id = $input['module_id'] ?? null;
    $section_id = $input['section_id'] ?? null;
    $session_time = $input['session_time'] ?? 0;
    $focused_time = $input['focus_data']['focused_time'] ?? 0;
    $unfocused_time = $input['focus_data']['unfocused_time'] ?? 0;
    $focus_percentage = $input['focus_data']['focus_percentage'] ?? 0;

    // Connect to database
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Insert or update eye tracking session
    $sql = "INSERT INTO eye_tracking_sessions 
            (user_id, module_id, section_id, session_time, focused_time, unfocused_time, focus_percentage, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            session_time = ?,
            focused_time = ?,
            unfocused_time = ?,
            focus_percentage = ?,
            updated_at = NOW()";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $user_id, $module_id, $section_id, $session_time, $focused_time, $unfocused_time, $focus_percentage,
        $session_time, $focused_time, $unfocused_time, $focus_percentage
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Session data saved successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
