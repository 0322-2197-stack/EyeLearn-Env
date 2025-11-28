<?php
/**
 * Centralized Database Configuration
 */

// Database credentials
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'capstone_db');

// Application settings
define('APP_ROOT', dirname(dirname(__FILE__)));
define('APP_URL', 'http://localhost/capstone - Copy');
define('DEBUG_MODE', getenv('DEBUG_MODE') ?: false);

// Session settings
ini_set('session.name', 'CAPSTONE_SESSION');
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_errors', 0);
}

// Helper function: Get PDO connection
function getDatabase() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    return $pdo;
}
?>
