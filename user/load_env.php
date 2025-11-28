<?php
/**
 * Simple .env file loader
 * Loads environment variables from .env file
 */

function loadEnvFile($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            // Set environment variable
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }

    return true;
}

// Load .env file from current directory or parent directory
$envLocations = [
    __DIR__ . '/.env',
    dirname(__DIR__) . '/.env'
];

foreach ($envLocations as $envPath) {
    if (loadEnvFile($envPath)) {
        break; // Load only the first found .env file
    }
}
?>
