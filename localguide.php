<?php
$pageTitle = "Local Guide | Powernet Digimart";
require_once 'includes/config.php';

$search = $_GET['search'] ?? '';
$place = $_GET['place'] ?? '';

$query = "SELECT s.*, u.username as owner_name FROM shops s JOIN users u ON s.owner_id = u.id WHERE 1=1";
$params = [];
if ($place) {
    $query .= " AND s.locality = ?";
    $params[] = $place;
}
if ($search) {
    $query .= " AND (s.name LIKE ? OR s.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$query .= " ORDER BY (s.type = 'privilege' OR s.type = 'classic' OR s.type = 'paid') DESC, s.name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$shops = $stmt->fetchAll();

$localities = $pdo->query("SELECT name FROM localities ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Local Business &amp; Shop Guide</h1>
            <p class="text-muted small mb-0">Discover verified stores and services near you</p>
        </div>
        <a href="index.php" class="btn btn-sm btn-outline-secondary">Back to Front</a>
    </div>

    <!-- Locality Selector Buttons -->
    <div class="card p-3 shadow-sm border-0 mb-4 bg-light">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="small fw-bold text-muted text-uppercase">Filter by Locality:</span>
            <div class="d-flex flex-wrap gap-1">
                <a href="localguide.php" class="badge rounded-pill text-decoration-none <?php echo empty($place) ? 'bg-success text-white' : 'bg-white text-dark border'; ?>">All Areas</a>
                <?php foreach(array_slice($localities, 0, 15) as $loc): ?>
                    <a href="localguide.php?place=<?php echo urlencode($loc); ?>" class="badge rounded-pill text-decoration-none <?php echo $place === $loc ? 'bg-success text-white' : 'bg-white text-muted border'; ?>">
                        <?php echo e($loc); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <?php if(empty($shops)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-store-slash fa-3x text-muted mb-3 opacity-50"></i>
                <h5>No local businesses found matching your selection.</h5>
                <a href="localguide.php" class="btn btn-sm btn-success mt-2">View All Shops</a>
            </div>
        <?php else: foreach($shops as $shop): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 shadow-sm border-0 overflow-hidden">
                    <div style="height: 100px; background: linear-gradient(135deg, #0b5d38, #39b54a);" class="position-relative">
                        <?php if(!empty($shop['wallpaper'])): ?>
                            <img src="<?php echo BASE_URL . '/' . e($shop['wallpaper']); ?>" class="w-100 h-100 object-fit-cover opacity-50">
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-3 pt-0">
                        <div class="d-flex align-items-end mb-2" style="margin-top: -30px;">
                            <div class="bg-white p-1 rounded shadow-sm me-2" style="width: 60px; height: 60px;">
                                <img src="<?php echo e($shop['logo'] ? BASE_URL . '/' . $shop['logo'] : 'https://picsum.photos/seed/' . $shop['id'] . '/100/100'); ?>" class="w-100 h-100 object-fit-contain rounded">
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?php echo e($shop['name']); ?></h6>
                                <small class="badge bg-light text-success border" style="font-size: 0.65rem;"><?php echo e(strtoupper($shop['type'])); ?></small>
                            </div>
                        </div>

                        <p class="small text-muted mb-3 line-clamp-2" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                            <?php echo e($shop['description'] ?: 'Leading merchant offering products and services.'); ?>
                        </p>

                        <div class="d-flex justify-content-between align-items-center pt-2 border-top small text-muted">
                            <span><i class="fas fa-map-marker-alt me-1 text-danger"></i><?php echo e($shop['locality'] ?: 'All Localities'); ?></span>
                            <a href="shop_detail.php?id=<?php echo $shop['id']; ?>" class="btn btn-sm btn-outline-success">View Shop</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
