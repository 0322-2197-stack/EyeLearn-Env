<?php
/**
 * Health Check Endpoint for Railway/PaaS Deployment
 * 
 * This endpoint returns a simple HTTP 200 response with JSON status.
 * Suitable for Railway health checks and monitoring.
 * 
 * Usage: Configure Railway health check to GET /health.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Test database connection if available
$db_status = 'unknown';
$db_error = null;

try {
    require_once __DIR__ . '/database/db_connection.php';
    $conn = getPDOConnection();
    $db_status = 'connected';
} catch (Exception $e) {
    $db_status = 'disconnected';
    $db_error = $e->getMessage();
}

// Return health status
http_response_code(200);
echo json_encode([
    'status' => 'ok',
    'service' => 'e-learning-platform',
    'timestamp' => date('c'),
    'database' => [
        'status' => $db_status,
        'error' => $db_error
    ]
]);
?>

