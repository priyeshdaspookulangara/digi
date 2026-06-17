<?php
// api/dashboard.php
require_once __DIR__ . '/api_helper.php';

api_require_role('shop_owner');

$shop_id = $_SESSION['shop_id'];

if (!$shop_id) {
    send_error("No shop associated with this account.", 404);
}

// Stats
$stats = [];

// Total Products
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE shop_id = ?");
$stmt->execute([$shop_id]);
$stats['total_products'] = $stmt->fetchColumn();

// New Enquiries (last 7 days)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM enquiries WHERE shop_id = ? AND created_at > datetime('now', '-7 days')");
$stmt->execute([$shop_id]);
$stats['new_enquiries'] = $stmt->fetchColumn();

// Active Offers (discounted products)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE shop_id = ? AND discount_entry IS NOT NULL AND discount_entry != ''");
$stmt->execute([$shop_id]);
$stats['active_offers'] = $stmt->fetchColumn();

// Recent Enquiries
$stmt = $pdo->prepare("SELECT e.*, p.name as product_name
                       FROM enquiries e
                       LEFT JOIN products p ON e.product_id = p.id
                       WHERE e.shop_id = ?
                       ORDER BY e.created_at DESC LIMIT 5");
$stmt->execute([$shop_id]);
$recent_enquiries = $stmt->fetchAll();

send_json([
    'stats' => $stats,
    'recent_enquiries' => $recent_enquiries
]);
?>
