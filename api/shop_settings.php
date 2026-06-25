<?php
// api/shop_settings.php
require_once __DIR__ . '/api_helper.php';

api_require_role('shop_owner');
// If admin, they can pass a shop_id in query params
$shop_id = ($_SESSION['role'] === 'admin' && isset($_GET['shop_id'])) ? $_GET['shop_id'] : ($_SESSION['shop_id'] ?? null);

if (!$shop_id) send_error("Shop ID context missing", 400);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    $shop = $stmt->fetch();
    if ($shop) {
        $c_stmt = $pdo->prepare("SELECT category_id FROM shop_category_map WHERE shop_id = ?");
        $c_stmt->execute([$shop_id]);
        $shop['categories'] = $c_stmt->fetchAll(PDO::FETCH_COLUMN);

        $t_stmt = $pdo->prepare("SELECT tag_id FROM shop_tag_map WHERE shop_id = ?");
        $t_stmt->execute([$shop_id]);
        $shop['tags'] = $t_stmt->fetchAll(PDO::FETCH_COLUMN);

        send_json($shop);
    } else {
        send_error("Shop not found", 404);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $name = $input['name'] ?? '';
    $description = $input['description'] ?? '';
    $meta_keywords = $input['meta_keywords'] ?? '';
    $og_title = $input['og_title'] ?? '';
    $og_description = $input['og_description'] ?? '';
    $social_links = $input['social_links'] ?? '';
    $locality = $input['locality'] ?? '';
    $categories = $input['categories'] ?? []; // Array of IDs
    $tags = $input['tags'] ?? []; // Array of IDs

    if (empty($name)) send_error("Shop name is required.");

    $stmt = $pdo->prepare("SELECT type FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    $shop_type = $stmt->fetchColumn();

    if ($shop_type === 'free_listing') {
        if (count($selected_categories) > 2) $selected_categories = array_slice($selected_categories, 0, 2);
        $selected_tags = [];
        $meta_keywords = $og_title = $og_description = $social_links = '';
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE shops SET name=?, description=?, meta_keywords=?, og_title=?, og_description=?, social_links=?, locality=? WHERE id=?");
        $stmt->execute([$name, $description, $meta_keywords, $og_title, $og_description, $social_links, $locality, $shop_id]);

        // Update Taxonomy
        if (!empty($categories)) {
            $pdo->prepare("DELETE FROM shop_category_map WHERE shop_id = ?")->execute([$shop_id]);
            $c_stmt = $pdo->prepare("INSERT INTO shop_category_map (shop_id, category_id) VALUES (?, ?)");
            foreach ($categories as $cid) $c_stmt->execute([$shop_id, $cid]);
        }
        if (!empty($tags)) {
            $pdo->prepare("DELETE FROM shop_tag_map WHERE shop_id = ?")->execute([$shop_id]);
            $t_stmt = $pdo->prepare("INSERT INTO shop_tag_map (shop_id, tag_id) VALUES (?, ?)");
            foreach ($tags as $tid) $t_stmt->execute([$shop_id, $tid]);
        }

        $pdo->commit();
        send_json(['message' => 'Shop settings updated successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        send_error($e->getMessage());
    }
} else {
    send_error("Method not allowed", 405);
}
?>
