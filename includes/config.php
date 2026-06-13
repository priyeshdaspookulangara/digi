<?php
// Configuration settings
define('BASE_URL', 'http://localhost:8000');
define('CSRF_SECRET', 'a_very_secret_token_12345');
define('DB_PATH', __DIR__ . '/../db/database.sqlite');

// Check for PDO extension
if (!class_exists('PDO')) {
    die("<strong>NexGen System Error:</strong> The PDO extension is missing. <br>
         Please enable <code>extension=pdo</code> and <code>extension=pdo_sqlite</code> (or <code>pdo_mysql</code>) in your php.ini.");
}

// Database Connection
try {
    $pdo = new PDO("sqlite:" . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Session Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// Authentication Helpers
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

function require_role($role) {
    require_login();
    if ($_SESSION['role'] !== $role && $_SESSION['role'] !== 'admin') {
        die("Access denied: Unauthorized role.");
    }
}

// Utility to escape output
function e($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}
?>
