<?php
require_once '../includes/config.php';
require_role('admin');

$banners = $pdo->query("SELECT * FROM banners ORDER BY display_order ASC, created_at DESC")->fetchAll();
$ads = $pdo->query("SELECT * FROM ads ORDER BY created_at DESC")->fetchAll();

$pageTitle = "Marketing Management | NexGen";
include '../includes/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="neon-text m-0">Marketing Management</h1>
        <div class="d-flex gap-2">
            <a href="manage_marketing.php?type=banner&action=add" class="neon-button-sm"><i class="fas fa-plus"></i> New Banner</a>
            <a href="manage_marketing.php?type=ad&action=add" class="neon-button-sm purple"><i class="fas fa-ad"></i> New Ad</a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Banners Section -->
        <div class="col-12">
            <div class="glass-card p-4">
                <h3 class="h5 mb-4 border-bottom border-secondary pb-2">Hero Banners (Sliders)</h3>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle">
                        <thead>
                            <tr class="text-uppercase small opacity-50">
                                <th>Preview</th>
                                <th>Info</th>
                                <th>Target</th>
                                <th>Performance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($banners as $b): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo BASE_URL . '/' . $b['image_url']; ?>" style="width: 120px; border-radius: 4px;">
                                </td>
                                <td>
                                    <strong><?php echo e($b['title']); ?></strong><br>
                                    <small class="opacity-50">Order: <?php echo $b['display_order']; ?></small>
                                </td>
                                <td><small><?php echo e($b['target_url']); ?></small></td>
                                <td>
                                    <div class="d-flex gap-3">
                                        <span><i class="far fa-eye text-info"></i> <?php echo $b['views']; ?></span>
                                        <span><i class="fas fa-mouse-pointer text-success"></i> <?php echo $b['clicks']; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if($b['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="manage_marketing.php?type=banner&action=edit&id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-light"><i class="fas fa-edit"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Ads Section -->
        <div class="col-12">
            <div class="glass-card p-4">
                <h3 class="h5 mb-4 border-bottom border-secondary pb-2">Advertisement Spaces</h3>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle">
                        <thead>
                            <tr class="text-uppercase small opacity-50">
                                <th>Preview</th>
                                <th>Position</th>
                                <th>Target</th>
                                <th>Performance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ads as $a): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo BASE_URL . '/' . $a['image_url']; ?>" style="width: 120px; border-radius: 4px;">
                                </td>
                                <td><span class="badge bg-info"><?php echo e($a['position']); ?></span></td>
                                <td><small><?php echo e($a['target_url']); ?></small></td>
                                <td>
                                    <div class="d-flex gap-3">
                                        <span><i class="far fa-eye text-info"></i> <?php echo $a['views']; ?></span>
                                        <span><i class="fas fa-mouse-pointer text-success"></i> <?php echo $a['clicks']; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if($a['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="manage_marketing.php?type=ad&action=edit&id=<?php echo $a['id']; ?>" class="btn btn-sm btn-outline-light"><i class="fas fa-edit"></i></a>
                                </td>
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
