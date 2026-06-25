<?php
require_once '../includes/config.php';
require_role('admin');

$pageTitle = "Tag Management | NexGen Admin";
require_once '../includes/admin_layout_header.php';

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die("CSRF Failed");

    $name = $_POST['name'];
    $icon = $_POST['icon'];
    $id = $_POST['id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE shop_tags SET name = ?, icon = ? WHERE id = ?");
        $stmt->execute([$name, $icon, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO shop_tags (name, icon) VALUES (?, ?)");
        $stmt->execute([$name, $icon]);
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM shop_tags WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
}

$tags = $pdo->query("SELECT * FROM shop_tags ORDER BY name ASC")->fetchAll();
?>

<div class="page__heading d-flex align-items-center">
    <div class="flex">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tag Management</li>
            </ol>
        </nav>
        <h1 class="m-0">Search Tags & Attributes</h1>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title m-0 fw-bold">Add / Edit Tag</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <input type="hidden" name="id" id="tag_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tag Name</label>
                        <input type="text" name="name" id="tag_name" class="form-control" placeholder="e.g. 24/7" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Material Icon Name</label>
                        <input type="text" name="icon" id="tag_icon" class="form-control" placeholder="e.g. wifi">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Save Tag</button>
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
                            <th class="ps-4">Icon</th>
                            <th>Name</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        <?php foreach($tags as $t): ?>
                        <tr>
                            <td class="ps-4">
                                <i class="material-icons text-primary"><?php echo e($t['icon'] ?: 'tag'); ?></i>
                            </td>
                            <td class="fw-bold"><?php echo e($t['name']); ?></td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-primary" onclick='editTag(<?php echo json_encode($t); ?>)'>Edit</button>
                                <a href="?delete=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this tag?')">Delete</a>
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
function editTag(tag) {
    document.getElementById('tag_id').value = tag.id;
    document.getElementById('tag_name').value = tag.name;
    document.getElementById('tag_icon').value = tag.icon || '';
}
function resetForm() {
    document.getElementById('tag_id').value = '';
    document.getElementById('tag_name').value = '';
    document.getElementById('tag_icon').value = '';
}
</script>

<?php require_once '../includes/admin_layout_footer.php'; ?>
