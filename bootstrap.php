<?php
/**
 * Shows Application Bootstrap
 * Initializes the application environment and dependencies
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

// Load configuration
$config = require_once __DIR__ . '/config/app.php';

// Start session with secure settings
session_start([
    'cookie_lifetime' => $config['security']['session_lifetime'],
    'cookie_secure' => false, // Set to true in production with HTTPS
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax'
]);

// Helper function to get config values
function config($key, $default = null) {
    global $config;
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }
    
    return $value;
}

// Helper function to check vulnerability flags
function isVulnerable($vulnerability) {
    return config("vulnerabilities.{$vulnerability}", false);
}

// Create logs directory if it doesn't exist
$logDir = dirname(config('logging.log_file'));
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

return $config;