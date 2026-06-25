<?php
// api/search.php
require_once __DIR__ . '/api_helper.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $search = $_GET['q'] ?? '';
    $locality = $_GET['locality'] ?? '';
    $cat_id = $_GET['cat_id'] ?? '';
    $tag_id = $_GET['tag_id'] ?? '';
    $type = $_GET['type'] ?? 'shops'; // 'shops' or 'products'

    $params = [];
    $where = ["1=1"];

    if ($type === 'shops') {
        $query = "SELECT DISTINCT s.* FROM shops s";
        if ($cat_id) {
            $query .= " JOIN shop_category_map scm ON s.id = scm.shop_id";
            $where[] = "scm.category_id = ?";
            $params[] = $cat_id;
        }
        if ($tag_id) {
            $query .= " JOIN shop_tag_map stm ON s.id = stm.shop_id";
            $where[] = "stm.tag_id = ?";
            $params[] = $tag_id;
        }
        if ($search) {
            $where[] = "(s.name LIKE ? OR s.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($locality) {
            $where[] = "s.locality = ?";
            $params[] = $locality;
        }
    } else {
        $query = "SELECT DISTINCT p.*, s.name as shop_name, s.locality FROM products p JOIN shops s ON p.shop_id = s.id";
        if ($cat_id) {
            $query .= " JOIN shop_category_map scm ON s.id = scm.shop_id";
            $where[] = "scm.category_id = ?";
            $params[] = $cat_id;
        }
        if ($tag_id) {
            $query .= " JOIN shop_tag_map stm ON s.id = stm.shop_id";
            $where[] = "stm.tag_id = ?";
            $params[] = $tag_id;
        }
        if ($search) {
            $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($locality) {
            $where[] = "s.locality = ?";
            $params[] = $locality;
        }
    }

    $query .= " WHERE " . implode(" AND ", $where);
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    send_json($stmt->fetchAll());
} else {
    send_error("Method not allowed", 405);
}
?>
