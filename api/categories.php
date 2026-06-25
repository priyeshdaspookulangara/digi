<?php
// api/categories.php
require_once __DIR__ . '/api_helper.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM shop_categories ORDER BY name ASC");
    send_json($stmt->fetchAll());
} elseif ($method === 'POST') {
    api_require_role('admin');
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $name = $input['name'] ?? '';
    $parent_id = $input['parent_id'] ?? null;
    $id = $input['id'] ?? null;

    if (empty($name)) send_error("Category name is required.");

    if ($id) {
        $stmt = $pdo->prepare("UPDATE shop_categories SET name = ?, parent_id = ? WHERE id = ?");
        $stmt->execute([$name, $parent_id, $id]);
        send_json(['message' => 'Category updated']);
    } else {
        $stmt = $pdo->prepare("INSERT INTO shop_categories (name, parent_id) VALUES (?, ?)");
        $stmt->execute([$name, $parent_id]);
        send_json(['message' => 'Category created', 'id' => $pdo->lastInsertId()]);
    }
} elseif ($method === 'DELETE') {
    api_require_role('admin');
    $id = $_GET['id'] ?? null;
    if (!$id) send_error("ID required");
    $stmt = $pdo->prepare("DELETE FROM shop_categories WHERE id = ?");
    $stmt->execute([$id]);
    send_json(['message' => 'Category deleted']);
} else {
    send_error("Method not allowed", 405);
}
?>
