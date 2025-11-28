<?php
/**
 * Save real-time eye tracking metrics to database
 */

require_once '../database/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    $user_id = $input['user_id'] ?? $_SESSION['user_id'] ?? 1;
    $module_id = $input['module_id'] ?? null;
    $section_id = $input['section_id'] ?? null;
    $metrics = $input['metrics'] ?? [];

    // Connect to database
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Insert real-time metrics
    $sql = "INSERT INTO eye_tracking_metrics 
            (user_id, module_id, section_id, attention_score, focus_percentage, is_focused, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $user_id,
        $module_id,
        $section_id,
        $metrics['attention_score'] ?? 0,
        $metrics['focus_percentage'] ?? 0,
        $metrics['is_focused'] ? 1 : 0
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Metrics saved successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
