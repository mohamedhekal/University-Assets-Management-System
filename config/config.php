<?php

/**
 * Application Configuration
 */

// Prevent direct access
if (!defined('APP_PATH')) {
    define('APP_PATH', dirname(__DIR__));
}

// Site Configuration
define('SITE_NAME', 'University Assets Management System');

// Auto-detect SITE_URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Get the base directory path relative to document root
$documentRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$basePath = str_replace('\\', '/', dirname(__DIR__)); // This is the application root directory

// Calculate the web-accessible path
$baseDir = '/';
if ($documentRoot && strpos($basePath, $documentRoot) === 0) {
    // If base path is within document root, get the relative path
    $relativePath = substr($basePath, strlen($documentRoot));
    $relativePath = trim($relativePath, '/');
    if ($relativePath !== '') {
        $baseDir = '/' . $relativePath;
    }
} else {
    // Fallback: extract from REQUEST_URI or SCRIPT_NAME
    // Find the /config/ directory in the request path to determine base
    $requestUri = $_SERVER['REQUEST_URI'] ?? $_SERVER['SCRIPT_NAME'] ?? '';
    $configPos = strpos($requestUri, '/config/');
    if ($configPos !== false) {
        $baseDir = substr($requestUri, 0, $configPos);
        if ($baseDir === '') {
            $baseDir = '/';
        }
    } else {
        // Last resort: use the directory name
        $baseDir = '/' . basename($basePath);
    }
}

define('SITE_URL', $protocol . '://' . $host . $baseDir);

define('TIMEZONE', 'Africa/Cairo');

// Paths
define('BASE_PATH', dirname(__DIR__));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('MODULES_PATH', BASE_PATH . '/modules');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('LOGS_PATH', BASE_PATH . '/logs');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set(TIMEZONE);

// Include Database
require_once BASE_PATH . '/config/database.php';
