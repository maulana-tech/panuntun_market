<?php
require_once dirname(__DIR__) . '/includes/functions.php';

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

// Simple routing for this application
if (isLoggedIn()) {
    // User is logged in, show dashboard
    if (file_exists(__DIR__ . '/dashboard.php')) {
        include __DIR__ . '/dashboard.php';
    } else {
        echo "Dashboard not found";
    }
} else {
    // User is not logged in, show login page
    if (file_exists(__DIR__ . '/login.php')) {
        include __DIR__ . '/login.php';
    } else {
        echo "Login page not found";
    }
}
?>

