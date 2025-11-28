<?php
/**
 * Get previous session data for a module/section
 */

require_once '../database/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    $user_id = $_SESSION['user_id'] ?? 1;
    $module_id = $_GET['module_id'] ?? null;
    $section_id = $_GET['section_id'] ?? null;

    if (!$module_id) {
        throw new Exception('module_id is required');
    }

    // Connect to database
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch latest session data
    $sql = "SELECT session_time, focused_time, unfocused_time, focus_percentage, created_at
            FROM eye_tracking_sessions
            WHERE user_id = ? AND module_id = ?";

    $params = [$user_id, $module_id];

    if ($section_id) {
        $sql .= " AND section_id = ?";
        $params[] = $section_id;
    }

    $sql .= " ORDER BY created_at DESC LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'session' => $session ?: null
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
