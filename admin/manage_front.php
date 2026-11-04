<?php
require_once '../includes/config.php';
require_role('admin');

$tab = $_GET['tab'] ?? 'products';
$csrf_token = get_csrf_token();

// Handle quick toggle actions
if (isset($_GET['toggle']) && isset($_GET['id']) && verify_csrf_token($_GET['csrf'] ?? '')) {
    $item_id = (int)$_GET['id'];
    $toggle_type = $_GET['toggle'];

    if ($toggle_type === 'product') {
        $pdo->prepare("UPDATE products SET show_on_front = CASE WHEN show_on_front = 1 THEN 0 ELSE 1 END WHERE id = ?")->execute([$item_id]);
    } elseif ($toggle_type === 'shop') {
        $pdo->prepare("UPDATE shops SET show_on_front = CASE WHEN show_on_front = 1 THEN 0 ELSE 1 END WHERE id = ?")->execute([$item_id]);
    } elseif ($toggle_type === 'service') {
        $pdo->prepare("UPDATE portfolios SET show_on_front = CASE WHEN show_on_front = 1 THEN 0 ELSE 1 END WHERE id = ?")->execute([$item_id]);
    }
    header("Location: manage_front.php?tab=" . urlencode($tab));
    exit;
}

// Fetch all products
$products = $pdo->query("SELECT p.*, s.name as shop_name FROM products p JOIN shops s ON p.shop_id = s.id ORDER BY p.created_at DESC")->fetchAll();

// Fetch all shops
$shops = $pdo->query("SELECT s.*, u.username as owner_name FROM shops s JOIN users u ON s.owner_id = u.id ORDER BY s.created_at DESC")->fetchAll();

// Fetch all services / portfolios
$services = $pdo->query("SELECT p.*, c.name as category_name, u.username FROM portfolios p LEFT JOIN professional_categories c ON p.category_id = c.id JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC")->fetchAll();

$pageTitle = "Front Page Showcase Manager | Admin";
require_once '../includes/admin_layout_header.php';
?>

<div class="page__heading d-flex align-items-center justify-content-between mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Front Page Items</li>
            </ol>
        </nav>
        <h1 class="m-0">Front Page Showcase Control</h1>
        <p class="text-muted small mb-0">Manage which products, shops, and professional services appear on the front page.</p>
    </div>
    <a href="../index.php" target="_blank" class="btn btn-primary fw-bold">
        <i class="material-icons align-middle me-1">visibility</i> View Front Page
    </a>
</div>

<!-- Tabs -->
<ul class="nav nav-pills mb-4">
    <li class="nav-item">
        <a class="nav-link fw-bold <?php echo $tab === 'products' ? 'active' : ''; ?>" href="manage_front.php?tab=products">
            <i class="material-icons align-middle me-1">inventory_2</i> Products (<?php echo count($products); ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-bold <?php echo $tab === 'shops' ? 'active' : ''; ?>" href="manage_front.php?tab=shops">
            <i class="material-icons align-middle me-1">store</i> Shops (<?php echo count($shops); ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-bold <?php echo $tab === 'services' ? 'active' : ''; ?>" href="manage_front.php?tab=services">
            <i class="material-icons align-middle me-1">work</i> Services / Classifieds (<?php echo count($services); ?>)
        </a>
    </li>
</ul>

<?php if ($tab === 'products'): ?>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="card-title m-0 fw-bold">Products on Front Page Shopping Grid</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Product</th>
                            <th>Shop</th>
                            <th>Price</th>
                            <th>Featured</th>
                            <th>Front Page Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No products found in database.</td></tr>
                        <?php else: foreach ($products as $p): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo e($p['image'] ?: 'https://via.placeholder.com/40'); ?>" class="rounded me-3" style="width: 44px; height: 44px; object-fit: cover;">
                                        <div>
                                            <div class="fw-bold"><?php echo e($p['name']); ?></div>
                                            <div class="small text-muted">#<?php echo $p['id']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo e($p['shop_name']); ?></td>
                                <td class="fw-bold">₹<?php echo number_format($p['price'], 2); ?></td>
                                <td>
                                    <?php if ($p['is_featured']): ?>
                                        <span class="badge bg-primary">Featured</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Standard</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!isset($p['show_on_front']) || $p['show_on_front']): ?>
                                        <span class="badge bg-success"><i class="material-icons align-middle" style="font-size:14px">check_circle</i> Visible on Front</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="material-icons align-middle" style="font-size:14px">visibility_off</i> Hidden</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="manage_front.php?tab=products&toggle=product&id=<?php echo $p['id']; ?>&csrf=<?php echo $csrf_token; ?>" class="btn btn-sm <?php echo (!isset($p['show_on_front']) || $p['show_on_front']) ? 'btn-outline-danger' : 'btn-outline-success'; ?> fw-bold">
                                        <?php echo (!isset($p['show_on_front']) || $p['show_on_front']) ? 'Remove from Front' : 'Show on Front'; ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($tab === 'shops'): ?>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="card-title m-0 fw-bold">Shops Displayed on Front Page</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Shop Name</th>
                            <th>Owner</th>
                            <th>Tier</th>
                            <th>Locality</th>
                            <th>Front Page Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($shops)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No shops found.</td></tr>
                        <?php else: foreach ($shops as $s): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <?php if ($s['logo']): ?>
                                            <img src="<?php echo BASE_URL . '/' . e($s['logo']); ?>" class="rounded me-3" style="width: 44px; height: 44px; object-fit: contain; background: #f3f3f3;">
                                        <?php else: ?>
                                            <div class="rounded me-3 bg-light d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;"><i class="material-icons text-muted">store</i></div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold"><?php echo e($s['name']); ?></div>
                                            <div class="small text-muted"><?php echo e($s['category'] ?: 'Uncategorized'); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo e($s['owner_name']); ?></td>
                                <td><span class="badge bg-info text-dark text-uppercase"><?php echo e($s['type']); ?></span></td>
                                <td><?php echo e($s['locality'] ?: 'All Localities'); ?></td>
                                <td>
                                    <?php if (!isset($s['show_on_front']) || $s['show_on_front']): ?>
                                        <span class="badge bg-success"><i class="material-icons align-middle" style="font-size:14px">check_circle</i> Visible on Front</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="material-icons align-middle" style="font-size:14px">visibility_off</i> Hidden</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="manage_front.php?tab=shops&toggle=shop&id=<?php echo $s['id']; ?>&csrf=<?php echo $csrf_token; ?>" class="btn btn-sm <?php echo (!isset($s['show_on_front']) || $s['show_on_front']) ? 'btn-outline-danger' : 'btn-outline-success'; ?> fw-bold">
                                        <?php echo (!isset($s['show_on_front']) || $s['show_on_front']) ? 'Remove from Front' : 'Show on Front'; ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($tab === 'services'): ?>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="card-title m-0 fw-bold">Professional Services &amp; Portfolios (Classifieds)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Professional / Title</th>
                            <th>Category</th>
                            <th>Contact / Locality</th>
                            <th>Front Page Classifieds</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($services)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No professional portfolios or services listed yet.</td></tr>
                        <?php else: foreach ($services as $srv): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?php echo e($srv['professional_name']); ?></div>
                                    <div class="small text-muted"><?php echo e($srv['title']); ?></div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?php echo e($srv['category_name'] ?: 'General'); ?></span></td>
                                <td>
                                    <div class="small"><?php echo e($srv['contact_phone'] ?: $srv['contact_email']); ?></div>
                                    <div class="small text-muted"><?php echo e($srv['locality']); ?></div>
                                </td>
                                <td>
                                    <?php if (!isset($srv['show_on_front']) || $srv['show_on_front']): ?>
                                        <span class="badge bg-success"><i class="material-icons align-middle" style="font-size:14px">check_circle</i> Featured in Classifieds</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="material-icons align-middle" style="font-size:14px">visibility_off</i> Hidden</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="manage_front.php?tab=services&toggle=service&id=<?php echo $srv['id']; ?>&csrf=<?php echo $csrf_token; ?>" class="btn btn-sm <?php echo (!isset($srv['show_on_front']) || $srv['show_on_front']) ? 'btn-outline-danger' : 'btn-outline-success'; ?> fw-bold">
                                        <?php echo (!isset($srv['show_on_front']) || $srv['show_on_front']) ? 'Remove from Front' : 'Show on Front'; ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../includes/admin_layout_footer.php'; ?>
