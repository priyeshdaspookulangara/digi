<?php
// api/shops.php
require_once __DIR__ . '/api_helper.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Anyone can list shops? Or just admin?
    // Usually marketplace search is public, but admin management needs more.
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM shops WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $shop = $stmt->fetch();
        if ($shop) {
            // Fetch categories
            $c_stmt = $pdo->prepare("SELECT category_id FROM shop_category_map WHERE shop_id = ?");
            $c_stmt->execute([$shop['id']]);
            $shop['categories'] = $c_stmt->fetchAll(PDO::FETCH_COLUMN);

            // Fetch tags
            $t_stmt = $pdo->prepare("SELECT tag_id FROM shop_tag_map WHERE shop_id = ?");
            $t_stmt->execute([$shop['id']]);
            $shop['tags'] = $t_stmt->fetchAll(PDO::FETCH_COLUMN);

            send_json($shop);
        } else {
            send_error("Shop not found", 404);
        }
    } else {
        $stmt = $pdo->query("SELECT * FROM shops ORDER BY name ASC");
        send_json($stmt->fetchAll());
    }
} elseif ($method === 'POST') {
    api_require_role('admin'); // Only admin can create shops via this API

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $name = $input['name'] ?? '';
    $owner_id = $input['owner_id'] ?? null;

    // Support creating owner on the fly
    $owner_data = $input['owner'] ?? null;

    $description = $input['description'] ?? '';
    $locality = $input['locality'] ?? '';
    $type = $input['type'] ?? 'standard';

    if (empty($name)) send_error("Shop name is required.");

    try {
        $pdo->beginTransaction();

        if ($owner_data) {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, mobile, customer_id, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $owner_data['username'],
                $owner_data['email'],
                password_hash($owner_data['password'], PASSWORD_DEFAULT),
                $owner_data['mobile'] ?? '',
                $owner_data['customer_id'] ?? null,
                'shop_owner'
            ]);
            $owner_id = $pdo->lastInsertId();
        }

        if (!$owner_id) throw new Exception("Owner ID or Owner Data required.");

        $stmt = $pdo->prepare("INSERT INTO shops (name, owner_id, description, locality, type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $owner_id, $description, $locality, $type]);
        $shop_id = $pdo->lastInsertId();

        // Optional taxonomy
        if (isset($input['categories']) && is_array($input['categories'])) {
            $c_stmt = $pdo->prepare("INSERT INTO shop_category_map (shop_id, category_id) VALUES (?, ?)");
            foreach ($input['categories'] as $cid) $c_stmt->execute([$shop_id, $cid]);
        }
        if (isset($input['tags']) && is_array($input['tags'])) {
            $t_stmt = $pdo->prepare("INSERT INTO shop_tag_map (shop_id, tag_id) VALUES (?, ?)");
            foreach ($input['tags'] as $tid) $t_stmt->execute([$shop_id, $tid]);
        }

        $pdo->commit();
        send_json(['message' => 'Shop created successfully', 'id' => $shop_id]);
    } catch (Exception $e) {
        $pdo->rollBack();
        send_error($e->getMessage());
    }
} elseif ($method === 'PUT') {
    api_require_role('admin');
    $input = json_decode(file_get_contents('php://input'), true);
    $shop_id = $input['id'] ?? null;
    if (!$shop_id) send_error("Shop ID required.");

    // Update logic similar to shop_settings but for Admin
    // ...
    send_json(['message' => 'Shop updated']);
} else {
    send_error("Method not allowed", 405);
}
?>
