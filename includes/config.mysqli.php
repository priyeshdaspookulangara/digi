<?php
/**
 * NexGen Database Connection Config (MySQLi Version)
 * Use this file if PDO is not available on your hosting environment.
 * Rename this file to includes/config.php to activate.
 */

define('BASE_URL', 'http://localhost:8000');
define('CSRF_SECRET', 'a_very_secret_token_12345');

// DB Credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');

// Database Connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Session Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF & Helpers
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

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

function e($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * PDO-to-MySQLi Compatibility Wrapper (Minimal)
 * This allows the code to continue using $pdo->prepare() style if possible.
 */
class NexGenDB {
    private $mysqli;
    public function __construct($mysqli) { $this->mysqli = $mysqli; }

    public function prepare($query) {
        // Convert :param to ? for basic queries
        $query = preg_replace('/:[a-zA-Z0-9_]+/', '?', $query);
        return new NexGenStmt($this->mysqli->prepare($query));
    }

    public function query($query) {
        $res = $this->mysqli->query($query);
        return new NexGenResult($res);
    }

    public function lastInsertId() {
        return $this->mysqli->insert_id;
    }
}

class NexGenStmt {
    private $stmt;
    public function __construct($stmt) { $this->stmt = $stmt; }
    public function execute($params = []) {
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $this->stmt->bind_param($types, ...$params);
        }
        return $this->stmt->execute();
    }
    public function fetch() {
        $res = $this->stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }
    public function fetchAll() {
        $res = $this->stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }
}

class NexGenResult {
    private $res;
    public function __construct($res) { $this->res = $res; }
    public function fetchColumn() {
        $row = $this->res->fetch_row();
        return $row ? $row[0] : null;
    }
    public function fetchAll() {
        return $this->res->fetch_all(MYSQLI_ASSOC);
    }
}

// Instantiate the wrapper to minimize changes in other files
$pdo = new NexGenDB($conn);
?>
