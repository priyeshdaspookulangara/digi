<?php
require_once '../includes/config.php';
require_role('admin');

$pageTitle = "Category Management | NexGen Admin";
require_once '../includes/admin_layout_header.php';

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die("CSRF Failed");

    $name = $_POST['name'];
    $parent_id = $_POST['parent_id'] ?: null;
    $id = $_POST['id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE shop_categories SET name = ?, parent_id = ? WHERE id = ?");
        $stmt->execute([$name, $parent_id, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO shop_categories (name, parent_id) VALUES (?, ?)");
        $stmt->execute([$name, $parent_id]);
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM shop_categories WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
}

$categories = $pdo->query("SELECT c.*, p.name as parent_name FROM shop_categories c LEFT JOIN shop_categories p ON c.parent_id = p.id ORDER BY c.name ASC")->fetchAll();
?>

<div class="page__heading d-flex align-items-center">
    <div class="flex">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Category Management</li>
            </ol>
        </nav>
        <h1 class="m-0">Shop Categories</h1>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title m-0 fw-bold">Add / Edit Category</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <input type="hidden" name="id" id="cat_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category Name</label>
                        <input type="text" name="name" id="cat_name" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Parent Category</label>
                        <select name="parent_id" id="cat_parent" class="form-select">
                            <option value="">-- No Parent (Root) --</option>
                            <?php foreach($categories as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo e($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Save Category</button>
                    <button type="button" onclick="resetForm()" class="btn btn-link btn-sm w-100 mt-2 text-muted">Reset</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr class="text-uppercase small text-muted">
                            <th class="ps-4">Name</th>
                            <th>Parent</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        <?php foreach($categories as $c): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?php echo e($c['name']); ?></td>
                            <td>
                                <?php if($c['parent_name']): ?>
                                    <span class="badge bg-light text-primary border"><?php echo e($c['parent_name']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">Root</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-primary" onclick='editCat(<?php echo json_encode($c); ?>)'>Edit</button>
                                <a href="?delete=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function editCat(cat) {
    document.getElementById('cat_id').value = cat.id;
    document.getElementById('cat_name').value = cat.name;
    document.getElementById('cat_parent').value = cat.parent_id || '';
}
function resetForm() {
    document.getElementById('cat_id').value = '';
    document.getElementById('cat_name').value = '';
    document.getElementById('cat_parent').value = '';
}
</script>

<?php require_once '../includes/admin_layout_footer.php'; ?>
