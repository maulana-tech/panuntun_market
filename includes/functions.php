<?php
// Start session first before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/config.php';

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // Use socket for localhost connections in XAMPP
            if ($this->host === 'localhost' || $this->host === '127.0.0.1') {
                $dsn = "mysql:unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock;dbname=" . $this->db_name;
            } else {
                $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name;
            }
            
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Authentication helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id_pengguna, nama, jabatan, email FROM pengguna WHERE id_pengguna = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_SESSION['user_id']);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Utility functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i:s', strtotime($datetime));
}

function showAlert($message, $type = 'info') {
    $alertClass = '';
    $iconClass = '';
    
    switch ($type) {
        case 'success':
            $alertClass = 'bg-green-50 border-green-200 text-green-800';
            $iconClass = 'text-green-400';
            break;
        case 'error':
            $alertClass = 'bg-red-50 border-red-200 text-red-800';
            $iconClass = 'text-red-400';
            break;
        case 'warning':
            $alertClass = 'bg-yellow-50 border-yellow-200 text-yellow-800';
            $iconClass = 'text-yellow-400';
            break;
        default:
            $alertClass = 'bg-blue-50 border-blue-200 text-blue-800';
            $iconClass = 'text-blue-400';
    }
    
    return "
    <div class='rounded-md border p-4 mb-4 {$alertClass}'>
        <div class='flex'>
            <div class='flex-shrink-0'>
                <svg class='h-5 w-5 {$iconClass}' viewBox='0 0 20 20' fill='currentColor'>
                    <path fill-rule='evenodd' d='M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z' clip-rule='evenodd' />
                </svg>
            </div>
            <div class='ml-3'>
                <p class='text-sm font-medium'>{$message}</p>
            </div>
        </div>
    </div>";
}

// CSRF Protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>

