<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    $shop_id = $_POST['shop_id'];
    $name = $_POST['customer_name'];
    $message = $_POST['message'];
    $product_id = $_POST['product_id'] ?? null;

    $stmt = $pdo->prepare("INSERT INTO enquiries (shop_id, product_id, customer_name, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$shop_id, $product_id, $name, $message]);

    header("Location: shop_detail.php?id=$shop_id&enquiry_sent=1");
    exit;
}
