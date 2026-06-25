<?php
// api/upload.php
require_once __DIR__ . '/api_helper.php';

api_require_role('shop_owner');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed", 405);
}

if (!isset($_FILES['image'])) {
    send_error("No image file uploaded");
}

$file = $_FILES['image'];
$type = $_POST['type'] ?? 'product'; // 'product', 'product_gallery', 'logo', 'wallpaper'
$id = $_POST['id'] ?? null; // product_id or shop_id

$stmt = $pdo->prepare("SELECT type FROM shops WHERE id = ?");
$stmt->execute([$_SESSION['shop_id']]);
$shop_type = $stmt->fetchColumn();

if ($shop_type === 'free_listing' && in_array($type, ['product_gallery', 'wallpaper'])) {
    send_error("Upgrade your shop to unlock gallery and wallpaper features.", 403);
}

$allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

if (!in_array($mime, $allowed_types)) {
    send_error("Invalid file type. Only JPG, PNG, and WEBP allowed.");
}

$upload_dir = '../uploads/';
if ($type === 'product') $upload_dir .= 'products/';
else $upload_dir .= 'shops/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$filename = uniqid() . '_' . basename($file['name']);
$target_path = $upload_dir . $filename;

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    $web_path = 'uploads/' . ($type === 'product' ? 'products/' : 'shops/') . $filename;

    // Update DB if ID provided
    if ($id) {
        if ($type === 'product') {
            $stmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ? AND shop_id = ?");
            $stmt->execute([$web_path, $id, $_SESSION['shop_id']]);
        } elseif ($type === 'product_gallery') {
            $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
            $stmt->execute([$id, $web_path]);
        } elseif ($type === 'logo') {
            $stmt = $pdo->prepare("UPDATE shops SET logo = ? WHERE id = ?");
            $stmt->execute([$web_path, $_SESSION['shop_id']]);
        } elseif ($type === 'wallpaper') {
            $stmt = $pdo->prepare("UPDATE shops SET wallpaper = ? WHERE id = ?");
            $stmt->execute([$web_path, $_SESSION['shop_id']]);
        }
    }

    send_json([
        'message' => 'Upload successful',
        'url' => $web_path
    ]);
} else {
    send_error("Failed to move uploaded file.");
}
?>
