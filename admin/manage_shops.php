<?php
require_once '../includes/config.php';
require_role('admin');

// Handle Category Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) die("CSRF failed");
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO shop_categories (name) VALUES (?)");
    $stmt->execute([$_POST['cat_name']]);
}

// Handle Shop Type/Category Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_shop'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) die("CSRF failed");
    $stmt = $pdo->prepare("UPDATE shops SET type = ?, category = ? WHERE id = ?");
    $stmt->execute([$_POST['shop_type'], $_POST['shop_category'], $_POST['shop_id']]);
}

$shops = $pdo->query("SELECT s.*, u.username as owner_name FROM shops s JOIN users u ON s.owner_id = u.id ORDER BY s.created_at DESC")->fetchAll();
$categories = $pdo->query("SELECT * FROM shop_categories ORDER BY name ASC")->fetchAll();

$pageTitle = "Shop Management | Admin";
include '../includes/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="neon-text m-0">Admin: Shop & Category Management</h1>
    </div>

    <div class="row g-4">
        <!-- Category Management -->
        <div class="col-lg-4">
            <div class="glass-card p-4">
                <h3 class="h5 mb-4">Manage Categories</h3>
                <form method="POST" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <div class="input-group">
                        <input type="text" name="cat_name" class="glass-input" placeholder="New Category" required>
                        <button type="submit" name="add_category" class="neon-button-sm">Add</button>
                    </div>
                </form>
                <div class="list-group list-group-flush bg-transparent">
                    <?php foreach($categories as $cat): ?>
                        <div class="list-group-item bg-transparent text-white border-secondary border-opacity-25 py-2">
                            <?php echo e($cat['name']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Shop List & Editing -->
        <div class="col-lg-8">
            <div class="glass-card p-4">
                <h3 class="h5 mb-4">All Registered Shops</h3>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle">
                        <thead>
                            <tr class="small opacity-50">
                                <th>Shop Name</th>
                                <th>Owner</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($shops as $s): ?>
                            <tr>
                                <td><strong><?php echo e($s['name']); ?></strong></td>
                                <td><?php echo e($s['owner_name']); ?></td>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                    <input type="hidden" name="shop_id" value="<?php echo $s['id']; ?>">
                                    <td>
                                        <select name="shop_type" class="glass-input p-1 small">
                                            <option value="privilege" <?php echo $s['type'] === 'privilege' ? 'selected' : ''; ?>>Privilege</option>
                                            <option value="classic" <?php echo $s['type'] === 'classic' ? 'selected' : ''; ?>>Classic</option>
                                            <option value="free_listing" <?php echo $s['type'] === 'free_listing' ? 'selected' : ''; ?>>Free Listing</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="shop_category" class="glass-input p-1 small">
                                            <?php foreach($categories as $cat): ?>
                                                <option value="<?php echo e($cat['name']); ?>" <?php echo $s['category'] === $cat['name'] ? 'selected' : ''; ?>><?php echo e($cat['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="submit" name="update_shop" class="btn btn-sm btn-outline-info">Save</button>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
