<?php
require_once 'includes/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON for API responses
if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    header('Content-Type: application/json');
}

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get request path and method
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove base path if exists
$path = str_replace('/cash-flow-fullstack-php', '', $path);

// Route to appropriate file
if ($path === '/' || $path === '/index.php') {
    if (isLoggedIn()) {
        include 'dashboard.php';
    } else {
        include 'login.php';
    }
} elseif (file_exists(__DIR__ . $path)) {
    include __DIR__ . $path;
} else {
    // Check if it's a PHP file without extension
    $phpFile = __DIR__ . $path . '.php';
    if (file_exists($phpFile)) {
        include $phpFile;
    } else {
        http_response_code(404);
        echo "Page not found";
    }
}
?>

