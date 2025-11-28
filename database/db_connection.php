<?php
/**
 * Centralized Database Connection
 * 
 * This file provides a single point of access for all database connections
 * in the application. It supports both PDO and mysqli connections.
 * 
 * Usage:
 *   require_once __DIR__ . '/../database/db_connection.php';
 *   $pdo = getPDOConnection();
 *   $conn = getMysqliConnection();
 */

// Database configuration
// Read from environment variables (for Railway/PaaS deployment) with local defaults
$db_config = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'dbname' => getenv('DB_NAME') ?: 'elearn_db',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'port' => getenv('DB_PORT') ?: '3306'
];

// Check if config_environment.php exists and use it for configuration
// Use a flag to prevent circular dependency when getDBConnection() calls this file
// IMPORTANT: Environment variables (from Docker/Railway) take precedence over config_environment.php
$env_config = null;
$fallback_config = [
    'host' => 'localhost',
    'dbname' => 'elearn_db',
    'username' => 'root',
    'password' => ''
];

// Only use config_environment.php if environment variables are NOT set (for local development)
// This ensures Docker environment variables always take precedence
$has_docker_env = getenv('DB_HOST') !== false || getenv('DB_USER') !== false || getenv('DB_PASS') !== false;

if (!$has_docker_env && file_exists(__DIR__ . '/../config_environment.php') && !defined('DB_CONN_INIT')) {
    define('DB_CONN_INIT', true);
    // Include config_environment.php - it returns the config array
    // The getDBConnection() function won't be called during this include
    $env_config = include __DIR__ . '/../config_environment.php';
    if (is_array($env_config) && isset($env_config['db_host'])) {
        $db_config = [
            'host' => $env_config['db_host'],
            'dbname' => $env_config['db_name'],
            'username' => $env_config['db_user'],
            'password' => $env_config['db_pass']
        ];
        
        // Store original config for fallback
        $fallback_config = $db_config;
    }
}

// Static variables to store connections (singleton pattern)
static $pdo_connection = null;
static $mysqli_connection = null;

/**
 * Get PDO database connection
 * Returns a singleton PDO instance
 * 
 * @return PDO
 * @throws PDOException
 */
function getPDOConnection() {
    global $pdo_connection, $db_config, $fallback_config, $env_config;
    
    if ($pdo_connection === null) {
        $attempted_config = $db_config;
        $last_error = null;
        
        try {
            // Include port in DSN if not default
            $port = isset($db_config['port']) && $db_config['port'] != '3306' ? ";port={$db_config['port']}" : '';
            $dsn = "mysql:host={$db_config['host']}{$port};dbname={$db_config['dbname']};charset=utf8mb4";
            $pdo_connection = new PDO($dsn, $db_config['username'], $db_config['password']);
            $pdo_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo_connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo_connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            $last_error = $e->getMessage();
            error_log("Database connection failed with primary config: " . $last_error);
            
            // If using environment config and it failed, try fallback to development credentials
            if ($env_config !== null && $db_config !== $fallback_config) {
                // Check if we're likely on localhost (development environment)
                $hostname = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 
                           (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
                $is_localhost = (
                    $hostname === 'localhost' ||
                    strpos($hostname, 'localhost') !== false ||
                    strpos($hostname, '127.0.0.1') !== false
                );
                
                if ($is_localhost) {
                    error_log("Attempting fallback to development credentials (localhost detected)");
                    try {
                        // Try development credentials as fallback
                        $dev_config = [
                            'host' => 'localhost',
                            'dbname' => 'elearn_db',
                            'username' => 'root',
                            'password' => '',
                            'port' => '3306'
                        ];
                        $port_str = isset($dev_config['port']) && $dev_config['port'] != '3306' ? ";port={$dev_config['port']}" : '';
                        $dsn = "mysql:host={$dev_config['host']}{$port_str};dbname={$dev_config['dbname']};charset=utf8mb4";
                        $pdo_connection = new PDO($dsn, $dev_config['username'], $dev_config['password']);
                        $pdo_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $pdo_connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                        $pdo_connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                        error_log("Successfully connected using development credentials fallback");
                    } catch (PDOException $e2) {
                        error_log("Fallback connection also failed: " . $e2->getMessage());
                        throw new PDOException("Database connection failed: " . $last_error . " (Fallback also failed: " . $e2->getMessage() . ")");
                    }
                } else {
                    throw new PDOException("Database connection failed: " . $last_error);
                }
            } else {
                throw new PDOException("Database connection failed: " . $last_error);
            }
        }
    }
    
    return $pdo_connection;
}

/**
 * Get mysqli database connection
 * Returns a singleton mysqli instance
 * 
 * @return mysqli
 * @throws Exception
 */
function getMysqliConnection() {
    global $mysqli_connection, $db_config, $env_config;
    
    if ($mysqli_connection === null) {
        $attempted_config = $db_config;
        $last_error = null;
        
        // Use port if specified and not default
        $port = isset($db_config['port']) && $db_config['port'] != '3306' ? (int)$db_config['port'] : null;
        $mysqli_connection = new mysqli(
            $db_config['host'],
            $db_config['username'],
            $db_config['password'],
            $db_config['dbname'],
            $port
        );
        
        if ($mysqli_connection->connect_error) {
            $last_error = $mysqli_connection->connect_error;
            error_log("Database connection failed with primary config: " . $last_error);
            
            // Close failed connection
            $mysqli_connection->close();
            $mysqli_connection = null;
            
            // If using environment config and it failed, try fallback to development credentials
            if ($env_config !== null) {
                // Check if we're likely on localhost (development environment)
                $hostname = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 
                           (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
                $is_localhost = (
                    $hostname === 'localhost' ||
                    strpos($hostname, 'localhost') !== false ||
                    strpos($hostname, '127.0.0.1') !== false
                );
                
                if ($is_localhost) {
                    error_log("Attempting fallback to development credentials (localhost detected)");
                    // Try development credentials as fallback
                    $dev_config = [
                        'host' => 'localhost',
                        'dbname' => 'elearn_db',
                        'username' => 'root',
                        'password' => '',
                        'port' => '3306'
                    ];
                    $dev_port = isset($dev_config['port']) && $dev_config['port'] != '3306' ? (int)$dev_config['port'] : null;
                    $mysqli_connection = new mysqli(
                        $dev_config['host'],
                        $dev_config['username'],
                        $dev_config['password'],
                        $dev_config['dbname'],
                        $dev_port
                    );
                    
                    if ($mysqli_connection->connect_error) {
                        $error_msg = "Connection failed: " . $last_error . " (Fallback also failed: " . $mysqli_connection->connect_error . ")";
                        error_log($error_msg);
                        $mysqli_connection->close();
                        $mysqli_connection = null;
                        throw new Exception($error_msg);
                    } else {
                        error_log("Successfully connected using development credentials fallback");
                    }
                } else {
                    $error_msg = "Connection failed: " . $last_error;
                    error_log($error_msg);
                    throw new Exception($error_msg);
                }
            } else {
                $error_msg = "Connection failed: " . $last_error;
                error_log($error_msg);
                throw new Exception($error_msg);
            }
        }
        
        // Set charset to utf8mb4 for proper Unicode support
        $mysqli_connection->set_charset("utf8mb4");
    }
    
    return $mysqli_connection;
}

/**
 * Close all database connections
 * Useful for cleanup or testing
 */
function closeConnections() {
    global $pdo_connection, $mysqli_connection;
    
    if ($pdo_connection !== null) {
        $pdo_connection = null;
    }
    
    if ($mysqli_connection !== null) {
        $mysqli_connection->close();
        $mysqli_connection = null;
    }
}

// For backward compatibility, create global variables if they don't exist
// This allows existing code to work without immediate refactoring
if (!isset($GLOBALS['pdo'])) {
    try {
        $GLOBALS['pdo'] = getPDOConnection();
    } catch (Exception $e) {
        // Silently fail if PDO connection cannot be established
    }
}

if (!isset($GLOBALS['conn'])) {
    try {
        $GLOBALS['conn'] = getMysqliConnection();
    } catch (Exception $e) {
        // Silently fail if mysqli connection cannot be established
    }
}

