<?php
// api/localities.php
require_once __DIR__ . '/api_helper.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM localities ORDER BY name ASC");
    send_json($stmt->fetchAll());
} else {
    send_error("Method not allowed", 405);
}
?>
