<?php
// Environment-specific configuration for EyeLearn

// Auto-detect environment based on server hostname
// This safely defaults to 'development' on localhost
if (!function_exists('detectEnvironment')) {
function detectEnvironment() {
    // Check if explicitly set via environment variable (for production deployments)
    if (isset($_ENV['APP_ENV'])) {
        return $_ENV['APP_ENV'];
    }
    
    // Check if explicitly set via constant
    if (defined('APP_ENVIRONMENT')) {
        return constant('APP_ENVIRONMENT');
    }
    
    // Auto-detect: if localhost or 127.0.0.1, use development
    $hostname = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 
                (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
    
    // Also check SERVER_ADDR for CLI scripts
    $server_addr = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '127.0.0.1';
    
    // Development indicators
    $is_localhost = (
        $hostname === 'localhost' ||
        $hostname === '127.0.0.1' ||
        strpos($hostname, 'localhost') !== false ||
        strpos($hostname, '127.0.0.1') !== false ||
        $server_addr === '127.0.0.1' ||
        $server_addr === '::1'
    );
    
    return $is_localhost ? 'development' : 'production';
}
} // End function_exists check

// Detect environment (safe default: development on localhost)
$environment = detectEnvironment();

if ($environment === 'development') {
    // Development settings
    $config = [
        'db_host' => getenv('DB_HOST') ?: 'localhost', // Use Docker env var if set
        'db_user' => getenv('DB_USER') ?: 'root',
        'db_pass' => getenv('DB_PASS') ?: '',
        'db_name' => getenv('DB_NAME') ?: 'elearn_db',
        'debug' => true,
        'error_reporting' => E_ALL,
        'display_errors' => 1,
        'base_url' => 'http://localhost/capstone/',
        'python_service_url' => 'http://localhost:5000'
    ];
} else {
    // Production settings - prioritize environment variables (for Docker/Railway deployments)
    $config = [
        'db_host' => getenv('DB_HOST') ?: 'localhost', // Use Docker env var if set
        'db_user' => getenv('DB_USER') ?: 'eyellearn_user', // Your production DB user
        'db_pass' => getenv('DB_PASS') ?: 'your_secure_password', // Your production DB password
        'db_name' => getenv('DB_NAME') ?: 'elearn_db',
        'debug' => false,
        'error_reporting' => 0,
        'display_errors' => 0,
        'base_url' => 'https://yourdomain.com/',
        'python_service_url' => 'http://localhost:5000'
    ];
}

// Apply settings
if ($config['debug']) {
    error_reporting($config['error_reporting']);
    ini_set('display_errors', $config['display_errors']);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Database connection - use centralized connection
if (!function_exists('getDBConnection')) {
function getDBConnection() {
    require_once __DIR__ . '/database/db_connection.php';
    return getMysqliConnection();
}
}

// Export config for use in other files
return $config;
?>
