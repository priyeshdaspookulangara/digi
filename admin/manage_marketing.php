<?php
require_once '../includes/config.php';
require_role('admin');

$type = $_GET['type'] ?? 'banner'; // banner or ad
$action = $_GET['action'] ?? 'add';
$id = $_GET['id'] ?? null;

$item = null;
if ($id) {
    $table = ($type === 'ad') ? 'ads' : 'banners';
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    $table = ($_POST['type'] === 'ad') ? 'ads' : 'banners';
    $target_url = $_POST['target_url'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $image_path = $_POST['existing_image'] ?? '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['image']['tmp_name']);
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if (in_array($mime, $allowed)) {
            $upload_dir = '../uploads/' . ($_POST['type'] === 'ad' ? 'ads/' : 'banners/');
            $file_ext = ($mime === 'image/jpeg') ? 'jpg' : (($mime === 'image/png') ? 'png' : 'webp');
            $filename = uniqid() . '.' . $file_ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $image_path = 'uploads/' . ($_POST['type'] === 'ad' ? 'ads/' : 'banners/') . $filename;
            }
        }
    }

    if ($_POST['action'] === 'add') {
        if ($_POST['type'] === 'banner') {
            $stmt = $pdo->prepare("INSERT INTO banners (image_url, target_url, title, description, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$image_path, $target_url, $_POST['title'], $_POST['description'], $_POST['display_order'], $is_active]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO ads (image_url, target_url, position, is_active) VALUES (?, ?, ?, ?)");
            $stmt->execute([$image_path, $target_url, $_POST['position'], $is_active]);
        }
    } elseif ($_POST['action'] === 'edit' && $id) {
        if ($_POST['type'] === 'banner') {
            $stmt = $pdo->prepare("UPDATE banners SET image_url = ?, target_url = ?, title = ?, description = ?, display_order = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$image_path, $target_url, $_POST['title'], $_POST['description'], $_POST['display_order'], $is_active, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE ads SET image_url = ?, target_url = ?, position = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$image_path, $target_url, $_POST['position'], $is_active, $id]);
        }
    }

    header("Location: marketing.php");
    exit;
}

$pageTitle = ($action === 'add' ? 'Add' : 'Edit') . ' ' . ucfirst($type);
include '../includes/header.php';
?>

<div class="container my-5">
    <div class="glass-card p-5 max-width-700 mx-auto">
        <h2 class="neon-text mb-4"><?php echo $pageTitle; ?></h2>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
            <input type="hidden" name="type" value="<?php echo e($type); ?>">
            <input type="hidden" name="action" value="<?php echo e($action); ?>">
            <?php if($item): ?>
                <input type="hidden" name="existing_image" value="<?php echo e($item['image_url']); ?>">
            <?php endif; ?>

            <div class="mb-4">
                <label class="form-label opacity-75">Upload Image</label>
                <input type="file" name="image" class="glass-input" <?php echo $item ? '' : 'required'; ?>>
                <?php if($item): ?>
                    <div class="mt-2">
                        <img src="<?php echo BASE_URL . '/' . $item['image_url']; ?>" alt="Preview" style="height: 100px; border-radius: 8px;">
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label opacity-75">Target URL (e.g. product_detail.php?id=5)</label>
                <input type="text" name="target_url" value="<?php echo $item ? e($item['target_url']) : ''; ?>" class="glass-input" placeholder="http://..." required>
            </div>

            <?php if($type === 'banner'): ?>
                <div class="mb-3">
                    <label class="form-label opacity-75">Banner Title</label>
                    <input type="text" name="title" value="<?php echo $item ? e($item['title']) : ''; ?>" class="glass-input">
                </div>
                <div class="mb-3">
                    <label class="form-label opacity-75">Description</label>
                    <textarea name="description" class="glass-input"><?php echo $item ? e($item['description']) : ''; ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label opacity-75">Display Order</label>
                    <input type="number" name="display_order" value="<?php echo $item ? e($item['display_order']) : '0'; ?>" class="glass-input">
                </div>
            <?php else: ?>
                <div class="mb-3">
                    <label class="form-label opacity-75">Ad Position</label>
                    <select name="position" class="glass-input">
                        <option value="below_hero_left" <?php echo ($item && $item['position'] == 'below_hero_left') ? 'selected' : ''; ?>>Below Hero (Left)</option>
                        <option value="below_hero_right" <?php echo ($item && $item['position'] == 'below_hero_right') ? 'selected' : ''; ?>>Below Hero (Right)</option>
                    </select>
                </div>
            <?php endif; ?>

            <div class="mb-4 form-check">
                <input type="checkbox" name="is_active" class="form-check-input" id="activeCheck" <?php echo (!$item || $item['is_active']) ? 'checked' : ''; ?>>
                <label class="form-check-label opacity-75" for="activeCheck">Active</label>
            </div>

            <div class="d-flex gap-3">
                <button type="submit" class="neon-button flex-grow-1"><?php echo $action === 'add' ? 'Create' : 'Save Changes'; ?></button>
                <a href="marketing.php" class="btn glass-card border-secondary px-4 d-flex align-items-center">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
