<?php
// api/bulk_shops.php
require_once __DIR__ . '/api_helper.php';

// Allow agents and admins to manage bulk shops
api_require_role('agent');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

// Get raw POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    send_error("Invalid JSON body. Expected a JSON array of shops.", 400);
}

$created_count = 0;
$updated_count = 0;
$error_count = 0;
$messages = [];

try {
    $db_in_transaction = $pdo->inTransaction();
    if (!$db_in_transaction) {
        $pdo->beginTransaction();
    }

    foreach ($input as $index => $item) {
        $shop_name = trim($item['shop_name'] ?? '');
        $owner_username = trim($item['owner_username'] ?? '');

        if (empty($shop_name) || empty($owner_username)) {
            $error_count++;
            $messages[] = "Row {$index}: Missing 'shop_name' or 'owner_username'.";
            continue;
        }

        $description = trim($item['description'] ?? '');
        $locality = trim($item['locality'] ?? '');
        $address = trim($item['address'] ?? '');
        $city = trim($item['city'] ?? '');
        $district = trim($item['district'] ?? '');
        $pincode = trim($item['pincode'] ?? '');
        $type = strtolower(trim($item['type'] ?? 'free_listing'));
        if (!in_array($type, ['privilege', 'classic', 'free_listing'])) {
            $type = 'free_listing';
        }

        $owner_email = trim($item['owner_email'] ?? '');
        if (empty($owner_email)) {
            $owner_email = $owner_username . '@example.com';
        }
        $owner_password = trim($item['owner_password'] ?? '');
        if (empty($owner_password)) {
            $owner_password = 'Pass123!';
        }
        $owner_mobile = trim($item['owner_mobile'] ?? '');
        $category_name = trim($item['category'] ?? '');

        // 1. Find or create Owner User
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$owner_username]);
        $owner_id = $stmt->fetchColumn();

        if (!$owner_id) {
            // Check email uniqueness to prevent unique constraint violation
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$owner_email]);
            if ($stmt->fetchColumn()) {
                $error_count++;
                $messages[] = "Row {$index}: User creation failed. Email '{$owner_email}' already exists.";
                continue;
            }

            // Create owner
            $hashed_pass = password_hash($owner_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, role, password, mobile) VALUES (?, ?, 'shop_owner', ?, ?)");
            $stmt->execute([$owner_username, $owner_email, $hashed_pass, $owner_mobile]);
            $owner_id = $pdo->lastInsertId();

            // Add to MLM hierarchy
            $stmt = $pdo->prepare("INSERT INTO mlm_hierarchy (user_id, parent_id, level_in_tree) VALUES (?, NULL, 1)");
            $stmt->execute([$owner_id]);
        }

        // 2. Find or create Category
        $category_id = null;
        if (!empty($category_name)) {
            $stmt = $pdo->prepare("SELECT id FROM shop_categories WHERE name = ?");
            $stmt->execute([$category_name]);
            $category_id = $stmt->fetchColumn();

            if (!$category_id) {
                $stmt = $pdo->prepare("INSERT INTO shop_categories (name) VALUES (?)");
                $stmt->execute([$category_name]);
                $category_id = $pdo->lastInsertId();
            }
        }

        // 3. Check if shop already exists for this owner and shop name (Upsert logic)
        $stmt = $pdo->prepare("SELECT id FROM shops WHERE owner_id = ? AND name = ?");
        $stmt->execute([$owner_id, $shop_name]);
        $existing_shop_id = $stmt->fetchColumn();

        if ($existing_shop_id) {
            // Update existing shop
            $stmt = $pdo->prepare("UPDATE shops SET description = ?, locality = ?, address = ?, city = ?, district = ?, pincode = ?, type = ?, category = ?, added_by_agent_id = ? WHERE id = ?");
            $stmt->execute([$description, $locality, $address, $city, $district, $pincode, $type, $category_name, $_SESSION['user_id'], $existing_shop_id]);
            $shop_id = $existing_shop_id;
            $updated_count++;
        } else {
            // Create new shop
            $stmt = $pdo->prepare("INSERT INTO shops (owner_id, name, description, locality, address, city, district, pincode, type, category, added_by_agent_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$owner_id, $shop_name, $description, $locality, $address, $city, $district, $pincode, $type, $category_name, $_SESSION['user_id']]);
            $shop_id = $pdo->lastInsertId();
            $created_count++;
        }

        // 4. Map Category Mapping
        if ($category_id) {
            // Remove existing maps to prevent duplicates
            $pdo->prepare("DELETE FROM shop_category_map WHERE shop_id = ? AND category_id = ?")->execute([$shop_id, $category_id]);
            $stmt = $pdo->prepare("INSERT INTO shop_category_map (shop_id, category_id) VALUES (?, ?)");
            $stmt->execute([$shop_id, $category_id]);
        }
    }

    if (!$db_in_transaction && $pdo->inTransaction()) {
        $pdo->commit();
    }

    send_json([
        'success' => true,
        'created_count' => $created_count,
        'updated_count' => $updated_count,
        'error_count' => $error_count,
        'messages' => $messages
    ]);

} catch (Exception $e) {
    if (!$db_in_transaction && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    send_error("Bulk import failed: " . $e->getMessage(), 500);
}
?>
