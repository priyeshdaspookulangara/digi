<?php
$pageTitle = "Shopping | Powernet Digimart";
require_once 'includes/header.php';

// Search and Filter Logic
$search = $_GET['search'] ?? '';
$locality = $_GET['locality'] ?? '';
$category_id = $_GET['cat_id'] ?? '';
$tag_id = $_GET['tag_id'] ?? '';

$query = "SELECT DISTINCT p.*, s.locality, s.category, s.type as shop_type FROM products p JOIN shops s ON p.shop_id = s.id";
$params = [];
$where_clauses = ["1=1"];

if ($search) {
    $where_clauses[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($locality) {
    $where_clauses[] = "s.locality = ?";
    $params[] = $locality;
}
if ($category_id) {
    $child_ids = [$category_id];
    $stmt = $pdo->prepare("SELECT id FROM shop_categories WHERE parent_id = ?");
    $stmt->execute([$category_id]);
    $child_ids = array_merge($child_ids, $stmt->fetchAll(PDO::FETCH_COLUMN));

    $placeholders = implode(',', array_fill(0, count($child_ids), '?'));
    $query .= " JOIN shop_category_map scm ON s.id = scm.shop_id";
    $where_clauses[] = "scm.category_id IN ($placeholders)";
    $params = array_merge($params, $child_ids);
}
if ($tag_id) {
    $query .= " JOIN shop_tag_map stm ON s.id = stm.shop_id";
    $where_clauses[] = "stm.tag_id = ?";
    $params[] = $tag_id;
}

$query .= " WHERE " . implode(" AND ", $where_clauses);
$query .= " ORDER BY (s.type = 'privilege' OR s.type = 'classic' OR s.type = 'paid') DESC, p.is_featured DESC, p.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories_data = $pdo->query("SELECT id, name FROM shop_categories WHERE parent_id IS NULL ORDER BY name ASC")->fetchAll();
?>

<main class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Shopping &amp; Product Directory</h1>
            <p class="text-muted small mb-0">Explore items and offers from registered merchants</p>
        </div>
        <a href="index.php" class="btn btn-sm btn-outline-secondary">Back to Front</a>
    </div>

    <!-- Active filter badges -->
    <?php if ($search || $category_id || $tag_id || $locality): ?>
    <div class="d-flex gap-2 mb-4">
        <?php if($search): ?><span class="badge rounded-pill bg-light text-dark border">Search: <?php echo e($search); ?> <a href="shopping.php" class="text-primary ms-1 text-decoration-none">&times;</a></span><?php endif; ?>
        <?php if($category_id): ?>
            <?php $cname = $pdo->query("SELECT name FROM shop_categories WHERE id = ".intval($category_id))->fetchColumn(); ?>
            <span class="badge rounded-pill bg-light text-dark border">Category: <?php echo e($cname); ?> <a href="shopping.php" class="text-primary ms-1 text-decoration-none">&times;</a></span>
        <?php endif; ?>
        <?php if($locality): ?><span class="badge rounded-pill bg-light text-dark border">Locality: <?php echo e($locality); ?> <a href="shopping.php" class="text-primary ms-1 text-decoration-none">&times;</a></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-3">
            <div class="card p-3 shadow-sm border-0 sticky-top" style="top: 90px;">
                <h6 class="fw-bold mb-3">Categories</h6>
                <div class="list-group list-group-flush mb-4">
                    <a href="shopping.php" class="list-group-item list-group-item-action border-0 px-0 py-2 <?php echo empty($category_id) ? 'fw-bold text-success' : 'text-dark'; ?>">All Categories</a>
                    <?php foreach($categories_data as $cat): ?>
                        <a href="shopping.php?cat_id=<?php echo $cat['id']; ?>" class="list-group-item list-group-item-action border-0 px-0 py-2 <?php echo $category_id == $cat['id'] ? 'fw-bold text-success' : 'text-dark'; ?>">
                            <?php echo e($cat['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <h6 class="fw-bold mb-2">Locality</h6>
                <?php $localities_db = $pdo->query("SELECT name FROM localities ORDER BY name ASC LIMIT 10")->fetchAll(PDO::FETCH_COLUMN); ?>
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach($localities_db as $loc): ?>
                        <a href="shopping.php?locality=<?php echo urlencode($loc); ?>" class="badge rounded-pill text-decoration-none <?php echo $locality === $loc ? 'bg-success text-white' : 'bg-light text-muted border'; ?>">
                            <?php echo e($loc); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted small"><?php echo count($products); ?> products found</span>
            </div>

            <div class="row g-3">
                <?php if (empty($products)): ?>
                    <div class="col-12 py-5 text-center text-muted">
                        <i class="fas fa-box-open fa-3x mb-3 text-secondary opacity-50"></i>
                        <h5>No products found matching criteria.</h5>
                        <a href="shopping.php" class="btn btn-sm btn-outline-success mt-2">Clear Filters</a>
                    </div>
                <?php else: foreach ($products as $product): ?>
                    <div class="col-xl-4 col-md-6">
                        <div class="card h-100 shadow-sm border-0 overflow-hidden">
                            <div class="position-relative" style="height: 220px; background: #f8f9fa;">
                                <img src="<?php echo e($product['image'] ?: 'https://picsum.photos/seed/' . $product['id'] . '/400/400'); ?>" alt="<?php echo e($product['name']); ?>" class="w-100 h-100 object-fit-cover">
                                <?php if($product['is_featured']): ?>
                                    <span class="badge bg-danger position-absolute top-0 start-0 m-2">FEATURED</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-direction-column justify-content-between p-3">
                                <div>
                                    <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="text-dark text-decoration-none">
                                        <h6 class="fw-bold mb-1 text-truncate"><?php echo e($product['name']); ?></h6>
                                    </a>
                                    <div class="text-muted small mb-2"><i class="fas fa-map-marker-alt me-1 text-danger"></i><?php echo e($product['locality'] ?: 'All Locations'); ?></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <span class="fw-bold text-success">₹<?php echo number_format($product['price'], 2); ?></span>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-secondary wishlist-btn" data-id="<?php echo $product['id']; ?>"><i class="far fa-heart"></i></button>
                                        <button class="btn btn-sm btn-danger add-to-cart-btn" data-id="<?php echo $product['id']; ?>">Buy Now</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
