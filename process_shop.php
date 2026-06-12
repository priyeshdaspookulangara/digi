<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }

    require_role('shop_owner');

    $shop_id = $_SESSION['shop_id'];
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $locality = $_POST['locality'] ?? '';

    // SEO
    $keywords = $_POST['meta_keywords'] ?? '';
    $og_title = $_POST['og_title'] ?? '';
    $og_description = $_POST['og_description'] ?? '';

    $upload_dir = 'uploads/shops/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $logo_path = $_POST['existing_logo'] ?? '';
    if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] === UPLOAD_ERR_OK) {
        $filename = uniqid('logo_') . '_' . $_FILES['shop_logo']['name'];
        if (move_uploaded_file($_FILES['shop_logo']['tmp_name'], $upload_dir . $filename)) {
            $logo_path = $upload_dir . $filename;
        }
    }

    $wallpaper_path = $_POST['existing_wallpaper'] ?? '';
    if (isset($_FILES['shop_wallpaper']) && $_FILES['shop_wallpaper']['error'] === UPLOAD_ERR_OK) {
        $filename = uniqid('wp_') . '_' . $_FILES['shop_wallpaper']['name'];
        if (move_uploaded_file($_FILES['shop_wallpaper']['tmp_name'], $upload_dir . $filename)) {
            $wallpaper_path = $upload_dir . $filename;
        }
    }

    try {
        $stmt = $pdo->prepare("UPDATE shops SET name = ?, description = ?, category = ?, locality = ?, logo = ?, wallpaper = ?, meta_keywords = ?, og_title = ?, og_description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $category, $locality, $logo_path, $wallpaper_path, $keywords, $og_title, $og_description, $shop_id]);
        header("Location: shop_portal.php?success=shop_updated");
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
    exit;
}
?>
