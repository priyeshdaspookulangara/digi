<?php
// api/api_helper.php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

function send_json($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function send_error($message, $status = 400) {
    send_json(['error' => $message], $status);
}

function api_require_login() {
    if (!isset($_SESSION['user_id'])) {
        send_error("Authentication required", 401);
    }
}

function api_require_role($role) {
    api_require_login();
    if ($_SESSION['role'] !== $role && $_SESSION['role'] !== 'admin') {
        send_error("Access denied: Unauthorized role", 403);
    }
}
?>
