<?php
// api/taxonomy.php
require_once __DIR__ . '/api_helper.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $type = $_GET['type'] ?? 'all'; // 'categories', 'tags', 'all'
    $data = [];

    if ($type === 'categories' || $type === 'all') {
        $stmt = $pdo->query("SELECT * FROM shop_categories ORDER BY parent_id ASC, name ASC");
        $data['categories'] = $stmt->fetchAll();
    }
    if ($type === 'tags' || $type === 'all') {
        $stmt = $pdo->query("SELECT * FROM shop_tags ORDER BY name ASC");
        $data['tags'] = $stmt->fetchAll();
    }

    send_json($data);
} else {
    send_error("Method not allowed", 405);
}
?>
