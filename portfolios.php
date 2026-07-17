<?php
require_once 'includes/config.php';

$search = $_GET['search'] ?? '';
$category_id = (int)($_GET['cat_id'] ?? 0);
$locality = $_GET['locality'] ?? '';

// Base query for portfolios with category names
$query = "
    SELECT p.*, c.name as category_name
    FROM portfolios p
    JOIN professional_categories c ON p.category_id = c.id
";
$params = [];
$where_clauses = ["1=1"];

if (!empty($search)) {
    $where_clauses[] = "(p.title LIKE ? OR p.professional_name LIKE ? OR p.skills LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_id > 0) {
    $where_clauses[] = "p.category_id = ?";
    $params[] = $category_id;
}

if (!empty($locality)) {
    $where_clauses[] = "p.locality = ?";
    $params[] = $locality;
}

$query .= " WHERE " . implode(" AND ", $where_clauses);
$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$portfolios = $stmt->fetchAll();

// Fetch categories and localities for sidebar filters
$categories = $pdo->query("SELECT * FROM professional_categories ORDER BY name ASC")->fetchAll();
$localities = $pdo->query("SELECT name FROM localities ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = "Professional Directory | NexGen Marketplace";
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="text-center mb-5">
        <h1 class="fw-extrabold text-dark mb-2">Professional Portfolios</h1>
        <p class="text-muted lead mx-auto" style="max-width: 600px;">Find qualified local professionals, freelancers, and service providers in your neighborhood.</p>
    </div>

    <!-- Top search bar -->
    <div class="glass-card p-4 mb-5 shadow-sm border-0 bg-white">
        <form method="GET" action="portfolios.php" class="row g-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, title, or skills..." value="<?php echo e($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="cat_id" class="form-select">
                    <option value="">-- All Categories --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>><?php echo e($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="locality" class="form-select">
                    <option value="">-- All Localities --</option>
                    <?php foreach ($localities as $loc): ?>
                        <option value="<?php echo e($loc); ?>" <?php echo $locality === $loc ? 'selected' : ''; ?>><?php echo e($loc); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100 fw-bold" style="background-color: #6366f1; border-color: #6366f1;">Go</button>
            </div>
        </form>
    </div>

    <div class="row g-4">
        <!-- Sidebar Category Filters -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="glass-card p-4 sticky-top bg-white shadow-sm border-0" style="top: 100px;">
                <h5 class="fw-bold text-dark mb-4 border-bottom pb-2">Filter Categories</h5>
                <div class="list-group list-group-flush bg-transparent">
                    <a href="portfolios.php" class="list-group-item list-group-item-action border-0 px-0 py-2 text-dark <?php echo $category_id === 0 ? 'fw-bold text-primary' : ''; ?>">
                        <i class="fas fa-th-large me-2 text-muted"></i> All Categories
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="portfolios.php?cat_id=<?php echo $cat['id']; ?>&locality=<?php echo urlencode($locality); ?>&search=<?php echo urlencode($search); ?>" class="list-group-item list-group-item-action border-0 px-0 py-2 text-dark <?php echo $category_id == $cat['id'] ? 'fw-bold text-primary' : ''; ?>">
                            - <?php echo e($cat['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Main Listing Grid -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold text-dark m-0">Listed Professionals</h4>
                <span class="text-muted small"><?php echo count($portfolios); ?> professionals found</span>
            </div>

            <div class="row g-4">
                <?php if (empty($portfolios)): ?>
                    <div class="col-12 text-center py-5">
                        <div class="text-muted mb-3"><i class="far fa-folder-open fa-3x"></i></div>
                        <h5 class="text-dark">No professional portfolios found</h5>
                        <p class="text-muted small">Try broadening your filters or keyword query.</p>
                        <a href="portfolios.php" class="btn btn-sm btn-outline-primary mt-2">Reset Filters</a>
                    </div>
                <?php else: foreach ($portfolios as $p): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="glass-card p-3 shadow-sm border-0 bg-white h-100 d-flex flex-column">
                            <!-- Image / Name layout -->
                            <div class="d-flex align-items-center gap-3 border-bottom pb-3 mb-3">
                                <img src="<?php echo $p['image'] ?: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop'; ?>" alt="<?php echo e($p['professional_name']); ?>" class="rounded-circle object-fit-cover shadow-sm border" style="width: 55px; height: 55px;">
                                <div class="overflow-hidden">
                                    <h5 class="fw-bold text-dark text-truncate m-0" style="font-size: 1rem;"><?php echo e($p['professional_name']); ?></h5>
                                    <span class="badge bg-soft-primary text-primary mt-1" style="font-size: 0.65rem;"><?php echo e($p['category_name']); ?></span>
                                </div>
                            </div>

                            <div class="flex-grow-1">
                                <h6 class="fw-bold text-dark mb-2"><?php echo e($p['title']); ?></h6>
                                <p class="text-muted small line-clamp-3 mb-3" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; font-size: 0.8rem;">
                                    <?php echo e($p['description'] ?: 'No bio available.'); ?>
                                </p>

                                <?php if (!empty($p['skills'])): ?>
                                    <div class="mb-3">
                                        <label class="form-label d-block text-muted small fw-bold mb-1">SKILLS</label>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php
                                            $skills_list = explode(',', $p['skills']);
                                            foreach (array_slice($skills_list, 0, 3) as $skill):
                                            ?>
                                                <span class="badge bg-light text-muted border" style="font-size: 0.6rem;"><?php echo e(trim($skill)); ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($skills_list) > 3): ?>
                                                <span class="badge bg-light text-muted border" style="font-size: 0.6rem;">+<?php echo count($skills_list) - 3; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mt-auto border-top pt-3 d-flex justify-content-between align-items-center">
                                <span class="text-muted small" style="font-size: 0.75rem;">
                                    <i class="fas fa-map-marker-alt me-1 text-primary"></i> <?php echo e($p['locality'] ?: 'Thrissur'); ?>
                                </span>
                                <a href="portfolio_detail.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-indigo px-3 fw-bold" style="background-color: #6366f1; color: white;">
                                    View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
