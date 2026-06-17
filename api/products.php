<?php
// api/products.php
require_once __DIR__ . '/api_helper.php';

api_require_role('shop_owner');
$shop_id = $_SESSION['shop_id'];

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND shop_id = ?");
            $stmt->execute([$_GET['id'], $shop_id]);
            $product = $stmt->fetch();
            if ($product) send_json($product);
            else send_error("Product not found", 404);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE shop_id = ? ORDER BY id DESC");
            $stmt->execute([$shop_id]);
            send_json($stmt->fetchAll());
        }
        break;

    case 'POST':
        // Add or Update
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST; // Fallback to form-data

        $id = $input['id'] ?? null;
        $name = $input['name'] ?? '';
        $price = $input['price'] ?? 0;
        $description = $input['description'] ?? '';
        $meta_keywords = $input['meta_keywords'] ?? '';
        $og_title = $input['og_title'] ?? '';
        $og_description = $input['og_description'] ?? '';
        $is_featured = isset($input['is_featured']) ? (int)$input['is_featured'] : 0;
        $discount_entry = $input['discount_entry'] ?? null;

        if (empty($name)) send_error("Product name is required.");

        if ($id) {
            // Update
            $stmt = $pdo->prepare("UPDATE products SET name=?, price=?, description=?, meta_keywords=?, og_title=?, og_description=?, is_featured=?, discount_entry=? WHERE id=? AND shop_id=?");
            $stmt->execute([$name, $price, $description, $meta_keywords, $og_title, $og_description, $is_featured, $discount_entry, $id, $shop_id]);
            send_json(['message' => 'Product updated successfully']);
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO products (shop_id, name, price, description, meta_keywords, og_title, og_description, is_featured, discount_entry) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$shop_id, $name, $price, $description, $meta_keywords, $og_title, $og_description, $is_featured, $discount_entry]);
            send_json(['message' => 'Product added successfully', 'id' => $pdo->lastInsertId()]);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) send_error("ID required");
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND shop_id = ?");
        $stmt->execute([$id, $shop_id]);
        send_json(['message' => 'Product deleted successfully']);
        break;

    default:
        send_error("Method not allowed", 405);
}
?>
