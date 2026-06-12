<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }

    require_role('shop_owner');

    $shop_id = $_SESSION['shop_id'];
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? 0;
    $description = $_POST['description'] ?? '';
    $keywords = $_POST['meta_keywords'] ?? '';
    $og_title = $_POST['og_title'] ?? '';
    $og_description = $_POST['og_description'] ?? '';
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // File handling
    $upload_dir = 'uploads/products/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $image_path = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['product_image']['tmp_name']);
        finfo_close($finfo);

        if (in_array($mime, $allowed_types)) {
            $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('img_') . '.' . $ext;
            $target = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target)) {
                $image_path = $target;
            }
        }
    }

    $video_path = '';
    if (isset($_FILES['product_video']) && $_FILES['product_video']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['video/mp4', 'video/webm'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['product_video']['tmp_name']);
        finfo_close($finfo);

        if (in_array($mime, $allowed_types)) {
            $ext = pathinfo($_FILES['product_video']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('vid_') . '.' . $ext;
            $target = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['product_video']['tmp_name'], $target)) {
                $video_path = $target;
            }
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO products (shop_id, name, description, price, image, video, is_featured, meta_keywords, og_title, og_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$shop_id, $name, $description, $price, $image_path, $video_path, $is_featured, $keywords, $og_title, $og_description]);
        header("Location: shop_portal.php?success=1");
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
    exit;
}
?>
