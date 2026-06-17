<?php
// api/shop_settings.php
require_once __DIR__ . '/api_helper.php';

api_require_role('shop_owner');
$shop_id = $_SESSION['shop_id'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    send_json($stmt->fetch());
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $name = $input['name'] ?? '';
    $description = $input['description'] ?? '';
    $meta_keywords = $input['meta_keywords'] ?? '';
    $og_title = $input['og_title'] ?? '';
    $og_description = $input['og_description'] ?? '';
    $social_links = $input['social_links'] ?? '';

    if (empty($name)) send_error("Shop name is required.");

    $stmt = $pdo->prepare("UPDATE shops SET name=?, description=?, meta_keywords=?, og_title=?, og_description=?, social_links=? WHERE id=?");
    $stmt->execute([$name, $description, $meta_keywords, $og_title, $og_description, $social_links, $shop_id]);

    send_json(['message' => 'Shop settings updated successfully']);
} else {
    send_error("Method not allowed", 405);
}
?>
