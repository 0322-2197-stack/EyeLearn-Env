<?php
/**
 * Get current user ID from session
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set response header
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    // Check if user is logged in
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => true,
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? 'Unknown'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'User not logged in'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
