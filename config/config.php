<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_market');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('APP_NAME', 'Panuntun');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost:/panuntun_market');

// Security configuration
define('JWT_SECRET', 'your-secret-key-here-change-in-production');
define('SESSION_TIMEOUT', 3600); // 1 hour

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session is started in functions.php to avoid conflicts
?>
