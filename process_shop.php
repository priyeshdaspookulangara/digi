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
    $locality = $_POST['locality'] ?? '';

    $selected_categories = $_POST['categories'] ?? [];
    $selected_tags = $_POST['tags'] ?? [];

    $stmt = $pdo->prepare("SELECT type FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    $shop_type = $stmt->fetchColumn();

    // Business Rules
    if ($shop_type === 'free_listing') {
        if (count($selected_categories) > 2) {
            $selected_categories = array_slice($selected_categories, 0, 2);
        }
        $selected_tags = []; // No tags for free listing
        $keywords = $og_title = $og_description = ''; // No SEO for free listing
    }

    // For backward compatibility, set 'category' to the first selected category name
    $category = "";
    if (!empty($selected_categories)) {
        $stmt = $pdo->prepare("SELECT name FROM shop_categories WHERE id = ?");
        $stmt->execute([$selected_categories[0]]);
        $category = $stmt->fetchColumn() ?: "";
    }

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
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['shop_logo']['tmp_name']);
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (in_array($mime, $allowed)) {
            $ext = ($mime === 'image/jpeg') ? 'jpg' : (($mime === 'image/png') ? 'png' : 'webp');
            $filename = uniqid('logo_') . '.' . $ext;
            if (move_uploaded_file($_FILES['shop_logo']['tmp_name'], $upload_dir . $filename)) {
                $logo_path = $upload_dir . $filename;
            }
        }
    }

    $wallpaper_path = $_POST['existing_wallpaper'] ?? '';
    if (isset($_FILES['shop_wallpaper']) && $_FILES['shop_wallpaper']['error'] === UPLOAD_ERR_OK) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['shop_wallpaper']['tmp_name']);
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (in_array($mime, $allowed)) {
            $ext = ($mime === 'image/jpeg') ? 'jpg' : (($mime === 'image/png') ? 'png' : 'webp');
            $filename = uniqid('wp_') . '.' . $ext;
            if (move_uploaded_file($_FILES['shop_wallpaper']['tmp_name'], $upload_dir . $filename)) {
                $wallpaper_path = $upload_dir . $filename;
            }
        }
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE shops SET name = ?, description = ?, category = ?, locality = ?, logo = ?, wallpaper = ?, meta_keywords = ?, og_title = ?, og_description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $category, $locality, $logo_path, $wallpaper_path, $keywords, $og_title, $og_description, $shop_id]);

        // Update Categories Map
        $pdo->prepare("DELETE FROM shop_category_map WHERE shop_id = ?")->execute([$shop_id]);
        if (!empty($selected_categories)) {
            $stmt = $pdo->prepare("INSERT INTO shop_category_map (shop_id, category_id) VALUES (?, ?)");
            foreach ($selected_categories as $cat_id) {
                $stmt->execute([$shop_id, $cat_id]);
            }
        }

        // Update Tags Map
        $pdo->prepare("DELETE FROM shop_tag_map WHERE shop_id = ?")->execute([$shop_id]);
        if (!empty($selected_tags)) {
            $stmt = $pdo->prepare("INSERT INTO shop_tag_map (shop_id, tag_id) VALUES (?, ?)");
            foreach ($selected_tags as $tag_id) {
                $stmt->execute([$shop_id, $tag_id]);
            }
        }

        $pdo->commit();
        header("Location: shop_portal.php?success=shop_updated");
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Database error: " . $e->getMessage());
    }
    exit;
}
?>
