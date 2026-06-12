<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login to use the wishlist.']);
    exit;
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? 0;

if ($action === 'toggle') {
    $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);

    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        echo json_encode(['success' => true, 'status' => 'removed']);
    } else {
        $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $product_id]);
        echo json_encode(['success' => true, 'status' => 'added']);
    }
}
?>
