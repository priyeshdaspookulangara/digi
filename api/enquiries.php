<?php
// api/enquiries.php
require_once __DIR__ . '/api_helper.php';

api_require_role('shop_owner');
$shop_id = $_SESSION['shop_id'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT e.*, p.name as product_name
                           FROM enquiries e
                           LEFT JOIN products p ON e.product_id = p.id
                           WHERE e.shop_id = ?
                           ORDER BY e.created_at DESC");
    $stmt->execute([$shop_id]);
    send_json($stmt->fetchAll());
} elseif ($method === 'POST') {
    // Mark as read
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    if (!$id) send_error("Enquiry ID required");

    $stmt = $pdo->prepare("UPDATE enquiries SET status = 'read' WHERE id = ? AND shop_id = ?");
    $stmt->execute([$id, $shop_id]);
    send_json(['message' => 'Enquiry marked as read']);
} else {
    send_error("Method not allowed", 405);
}
?>
