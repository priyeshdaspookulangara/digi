<?php
// api/tags.php
require_once __DIR__ . '/api_helper.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM shop_tags ORDER BY name ASC");
    send_json($stmt->fetchAll());
} elseif ($method === 'POST') {
    api_require_role('admin');
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $name = $input['name'] ?? '';
    $id = $input['id'] ?? null;

    if (empty($name)) send_error("Tag name is required.");

    if ($id) {
        $stmt = $pdo->prepare("UPDATE shop_tags SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        send_json(['message' => 'Tag updated']);
    } else {
        $stmt = $pdo->prepare("INSERT INTO shop_tags (name) VALUES (?)");
        $stmt->execute([$name]);
        send_json(['message' => 'Tag created', 'id' => $pdo->lastInsertId()]);
    }
} elseif ($method === 'DELETE') {
    api_require_role('admin');
    $id = $_GET['id'] ?? null;
    if (!$id) send_error("ID required");
    $stmt = $pdo->prepare("DELETE FROM shop_tags WHERE id = ?");
    $stmt->execute([$id]);
    send_json(['message' => 'Tag deleted']);
} else {
    send_error("Method not allowed", 405);
}
?>
