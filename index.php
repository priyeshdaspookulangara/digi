<?php
$pageTitle = "NexGen Marketplace | High-Tech MLM Ecosystem";
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
    // Support hierarchical search: find category and all its children
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
$query .= " ORDER BY p.is_featured DESC, p.created_at DESC LIMIT 20";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get top-level categories for the circle icons
$categories_data = $pdo->query("SELECT id, name FROM shop_categories WHERE parent_id IS NULL ORDER BY name ASC")->fetchAll();

// Fetch Banners
$banners = $pdo->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
// Record Banner Views
foreach ($banners as $b) {
    $pdo->prepare("UPDATE banners SET views = views + 1 WHERE id = ?")->execute([$b['id']]);
}

// Fetch Ads
$ads = $pdo->query("SELECT * FROM ads WHERE is_active = 1")->fetchAll();
// Organize ads by position
$ads_by_pos = [];
foreach ($ads as $a) {
    $ads_by_pos[$a['position']] = $a;
    // Record Ad Views
    $pdo->prepare("UPDATE ads SET views = views + 1 WHERE id = ?")->execute([$a['id']]);
}

// Mock fallback for products
if (empty($products)) {
    $products = [
        ['id' => 1, 'shop_id' => 1, 'locality' => 'Thrissur', 'name' => 'CyberPulse Smartwatch', 'price' => 12500, 'image' => 'https://images.unsplash.com/photo-1544117519-31a4b719223d?w=600', 'is_featured' => 1, 'description' => 'AI health tracking.', 'shop_type' => 'privilege', 'discount_entry' => '10% OFF'],
        ['id' => 2, 'shop_id' => 1, 'locality' => 'Thrissur', 'name' => 'Neon Drift Headphones', 'price' => 8900, 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600', 'is_featured' => 0, 'description' => 'Cyberpunk aesthetics.', 'shop_type' => 'standard', 'discount_entry' => ''],
        ['id' => 3, 'shop_id' => 1, 'locality' => 'Kochi', 'name' => 'Quantum VR Headset', 'price' => 45000, 'image' => 'https://images.unsplash.com/photo-1622979135225-d2ba269cf1ac?w=600', 'is_featured' => 1, 'description' => 'Next-gen immersion.', 'shop_type' => 'privilege', 'discount_entry' => '5% OFF'],
        ['id' => 4, 'shop_id' => 1, 'locality' => 'Kochi', 'name' => 'AeroDrone X Pro', 'price' => 72000, 'image' => 'https://images.unsplash.com/photo-1473968512647-3e44a224fe8f?w=600', 'is_featured' => 0, 'description' => '4K stability.', 'shop_type' => 'standard', 'discount_entry' => '']
    ];
}
?>

<main class="container mt-4">
    <!-- Feature Boxes -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="glass-card p-4 h-100 feature-box" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#locationModal">
                <div class="mb-3">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <h3 class="h5">Local Shops</h3>
                <p class="small text-muted">Find the best deals in your neighborhood.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card p-4 h-100 feature-box">
                <div class="mb-3">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3 class="h5">Flash Sales</h3>
                <p class="small text-muted">Exclusive limited-time offers.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card p-4 h-100 feature-box">
                <div class="mb-3">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3 class="h5">Verified Vendors</h3>
                <p class="small text-muted">Secure shopping with trusted partners.</p>
            </div>
        </div>
    </div>

    <!-- Hero Section / Dynamic Slider -->
    <section class="hero-banner mb-4">
        <?php if (!empty($banners)): ?>
            <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <?php foreach($banners as $index => $b): ?>
                        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>"></button>
                    <?php endforeach; ?>
                </div>
                <div class="carousel-inner glass-card p-0 overflow-hidden" style="height: 450px;">
                    <?php foreach($banners as $index => $b): ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?> h-100">
                        <a href="track.php?type=banner&id=<?php echo $b['id']; ?>">
                            <img src="<?php echo BASE_URL . '/' . $b['image_url']; ?>" class="d-block w-100 h-100 object-fit-cover" alt="<?php echo e($b['title']); ?>">
                            <div class="carousel-caption d-none d-md-block text-start" style="left: 10%; bottom: 20%;">
                                <h1 class="display-3 fw-bold mb-3"><?php echo e($b['title']); ?></h1>
                                <p class="h4 mb-4 fw-light text-muted"><?php echo e($b['description']); ?></p>
                                <span class="btn btn-lg neon-button px-5">SHOP NOW</span>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>
        <?php else: ?>
            <div class="glass-card p-0 overflow-hidden position-relative" style="height: 450px;">
                <img src="https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&q=80&w=1200" alt="Hero" class="w-100 h-100 object-fit-cover">
                <div class="position-absolute top-0 start-0 w-100 h-100 hero-overlay d-flex align-items-center">
                    <div class="container px-5 hero-content">
                        <h1 class="display-3 fw-bold mb-3">UP TO <span class="text-primary">50% OFF</span></h1>
                        <p class="h3 mb-4 fw-light text-muted">On Premium High-Tech Selection</p>
                        <a href="#" class="btn btn-lg neon-button px-5">SHOP NOW</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- Ad Spaces Underneath -->
    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <?php if(isset($ads_by_pos['below_hero_left'])): ?>
                <a href="track.php?type=ad&id=<?php echo $ads_by_pos['below_hero_left']['id']; ?>" class="d-block glass-card p-0 overflow-hidden" style="height: 180px;">
                    <img src="<?php echo BASE_URL . '/' . $ads_by_pos['below_hero_left']['image_url']; ?>" class="w-100 h-100 object-fit-cover ad-hover-effect">
                </a>
            <?php else: ?>
                <div class="glass-card d-flex align-items-center justify-content-center opacity-25" style="height: 180px; border-style: dashed;">
                    <span class="small">AD SPACE (LEFT)</span>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <?php if(isset($ads_by_pos['below_hero_right'])): ?>
                <a href="track.php?type=ad&id=<?php echo $ads_by_pos['below_hero_right']['id']; ?>" class="d-block glass-card p-0 overflow-hidden" style="height: 180px;">
                    <img src="<?php echo BASE_URL . '/' . $ads_by_pos['below_hero_right']['image_url']; ?>" class="w-100 h-100 object-fit-cover ad-hover-effect">
                </a>
            <?php else: ?>
                <div class="glass-card d-flex align-items-center justify-content-center opacity-25" style="height: 180px; border-style: dashed;">
                    <span class="small">AD SPACE (RIGHT)</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Category Circles -->
    <section class="categories-section mb-5">
        <h4 class="section-title">Browse Categories</h4>
        <div class="category-scroll d-flex flex-wrap">
            <?php
            $icons = ['Electronics' => 'fas fa-plug', 'Fashion' => 'fas fa-tshirt', 'Home' => 'fas fa-home', 'Beauty' => 'fas fa-magic', 'Sports' => 'fas fa-running', 'Toys' => 'fas fa-gamepad'];
            foreach ($categories_data as $cat):
                $icon = $icons[$cat['name']] ?? 'fas fa-th-large';
            ?>
            <div class="category-item text-center" style="width: 120px;" onclick="window.location.href='index.php?cat_id=<?php echo $cat['id']; ?>'">
                <div class="category-circle">
                    <i class="<?php echo $icon; ?>"></i>
                </div>
                <div class="category-name"><?php echo e($cat['name']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Filter & Search Summary (Mobile/Active Filters) -->
    <?php if ($search || $category_id || $tag_id || $locality): ?>
    <div class="d-flex gap-2 mb-4">
        <?php if($search): ?><span class="badge rounded-pill glass-card text-dark border-light">Search: <?php echo e($search); ?> <a href="index.php" class="text-primary ms-1 text-decoration-none">&times;</a></span><?php endif; ?>
        <?php if($category_id): ?>
            <?php $cname = $pdo->query("SELECT name FROM shop_categories WHERE id = ".intval($category_id))->fetchColumn(); ?>
            <span class="badge rounded-pill glass-card text-dark border-light">Category: <?php echo e($cname); ?> <a href="index.php" class="text-primary ms-1 text-decoration-none">&times;</a></span>
        <?php endif; ?>
        <?php if($tag_id): ?>
            <?php $tname = $pdo->query("SELECT name FROM shop_tags WHERE id = ".intval($tag_id))->fetchColumn(); ?>
            <span class="badge rounded-pill glass-card text-dark border-light">Tag: <?php echo e($tname); ?> <a href="index.php" class="text-primary ms-1 text-decoration-none">&times;</a></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Product Showcase -->
    <section class="products-section">
        <div class="row g-4">
            <!-- Sidebar Filters (Desktop Only) -->
            <div class="col-lg-3 d-none d-lg-block">
                <div class="glass-card p-4 sticky-top" style="top: 100px;">
                    <h5 class="fw-bold mb-4">Filters</h5>

                    <!-- Hierarchical Categories -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-3">Shop Categories</label>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php
                            $parents = $pdo->query("SELECT * FROM shop_categories WHERE parent_id IS NULL ORDER BY name ASC")->fetchAll();
                            foreach($parents as $p):
                                $children = $pdo->prepare("SELECT * FROM shop_categories WHERE parent_id = ? ORDER BY name ASC");
                                $children->execute([$p['id']]);
                                $child_list = $children->fetchAll();
                            ?>
                                <div class="mb-2">
                                    <a href="index.php?cat_id=<?php echo $p['id']; ?>" class="text-decoration-none text-dark fw-bold small <?php echo $category_id == $p['id'] ? 'text-primary' : ''; ?>">
                                        <?php echo e($p['name']); ?>
                                    </a>
                                    <?php if(!empty($child_list)): ?>
                                        <div class="ms-3 mt-1">
                                            <?php foreach($child_list as $c): ?>
                                                <a href="index.php?cat_id=<?php echo $c['id']; ?>" class="d-block text-decoration-none text-muted small mb-1 <?php echo $category_id == $c['id'] ? 'text-primary fw-bold' : ''; ?>">
                                                    - <?php echo e($c['name']); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Attribute Tags -->
                    <div class="mb-4 pt-3 border-top">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-3">Amenities / Tags</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php
                            $tags = $pdo->query("SELECT * FROM shop_tags ORDER BY name ASC")->fetchAll();
                            foreach($tags as $t):
                            ?>
                                <a href="index.php?tag_id=<?php echo $t['id']; ?>" class="badge rounded-pill border text-decoration-none px-3 py-2 <?php echo $tag_id == $t['id'] ? 'bg-primary text-white border-primary' : 'bg-white text-muted'; ?>">
                                    <?php echo e($t['name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <a href="index.php" class="btn btn-sm btn-link text-primary p-0 text-decoration-none small">Clear All Filters</a>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-end mb-4">
                    <h4 class="section-title mb-0">
                        <?php
                        if($category_id) echo "Category Search";
                        elseif($tag_id) echo "Tagged Shops";
                        elseif($search) echo "Search Results";
                        else echo "Deals of the Day";
                        ?>
                    </h4>
                    <span class="text-muted small"><?php echo count($products); ?> items found</span>
                </div>

                <div class="row g-4">
            <?php foreach ($products as $product): ?>
            <div class="col-xl-4 col-lg-6 col-md-6">
                <div class="glass-card product-card p-3">
                    <div class="product-img-wrapper">
                        <?php if($product['is_featured']): ?>
                            <span class="badge bg-primary position-absolute top-0 start-0 m-2 z-3 shadow">FEATURED</span>
                        <?php endif; ?>
                        <button class="wishlist-btn" data-id="<?php echo $product['id']; ?>"><i class="far fa-heart"></i></button>
                        <img src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                    </div>
                    <div class="product-info mt-2">
                        <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="text-dark text-decoration-none">
                            <h5 class="product-title"><?php echo e($product['name']); ?></h5>
                        </a>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <span class="product-price">₹<?php echo number_format($product['price'], 2); ?></span>
                                <?php if($product['shop_type'] === 'privilege' && $product['discount_entry']): ?>
                                    <div class="small text-primary fw-bold" style="font-size: 0.7rem;"><?php echo e($product['discount_entry']); ?></div>
                                <?php endif; ?>
                            </div>
                            <button class="btn btn-sm btn-outline-primary rounded-circle add-to-cart-btn" data-id="<?php echo $product['id']; ?>"><i class="fas fa-plus"></i></button>
                        </div>
                        <!-- Shop Tags Display -->
                        <div class="mt-2 pt-2 border-top d-flex flex-wrap gap-1">
                            <?php
                            $stags = $pdo->prepare("SELECT t.name FROM shop_tags t JOIN shop_tag_map m ON t.id = m.tag_id WHERE m.shop_id = ? LIMIT 3");
                            $stags->execute([$product['shop_id']]);
                            $tag_names = $stags->fetchAll(PDO::FETCH_COLUMN);
                            foreach($tag_names as $tn):
                            ?>
                                <span class="badge bg-light text-muted fw-normal border" style="font-size: 0.6rem;"><?php echo e($tn); ?></span>
                            <?php endforeach; ?>
                            <span class="ms-auto text-muted" style="font-size: 0.65rem;"><i class="fas fa-map-marker-alt me-1"></i><?php echo e($product['locality']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- MLM Benefits Promo -->
    <section class="mlm-promo mt-5 py-5">
        <div class="glass-card p-5 text-center">
            <h2 class="text-primary mb-4">EARN WHILE YOU SHOP</h2>
            <p class="lead mb-4 mx-auto text-muted" style="max-width: 800px;">Join our 10-level referral ecosystem and unlock massive commissions. Transform your marketplace experience into a wealth-building journey.</p>
            <div class="row g-4 mt-2">
                <div class="col-md-4">
                    <div class="p-3 border-end">
                        <h1 class="text-primary">10</h1>
                        <p class="text-uppercase small fw-bold text-muted">Benefit Levels</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border-end">
                        <h1 class="text-primary">₹3,000</h1>
                        <p class="text-uppercase small fw-bold text-muted">One-Time Activation</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3">
                        <h1 class="text-primary">∞</h1>
                        <p class="text-uppercase small fw-bold text-muted">Rebirth Potential</p>
                    </div>
                </div>
            </div>
            <a href="register.php" class="btn btn-lg neon-button px-5 mt-5">GET STARTED NOW</a>
        </div>
    </section>
</main>

<!-- Location Selection Modal -->
<div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card">
            <div class="modal-header border-0">
                <h5 class="modal-title text-primary">Choose your locality</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Geolocation Button -->
                <button id="detectLocationBtn" class="btn neon-button-sm w-100 mb-4 py-3">
                    <i class="fas fa-crosshairs me-2"></i> DETECT MY LOCATION
                </button>

                <div id="locationLoader" class="text-center d-none mb-3">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="small mt-2 text-muted">Finding nearest center...</p>
                </div>

                <div class="locality-list-wrapper" style="max-height: 400px; overflow-y: auto;">
                    <?php
                    // Fetch from the new localities table
                    $localities_db = $pdo->query("SELECT name FROM localities ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
                    ?>
                    <div class="list-group list-group-flush bg-transparent">
                        <?php foreach($localities_db as $loc): ?>
                            <a href="local-shops.php?place=<?php echo urlencode($loc); ?>" class="list-group-item list-group-item-action bg-transparent text-dark border-light py-3 d-flex justify-content-between align-items-center">
                                <?php echo e($loc); ?>
                                <i class="fas fa-chevron-right small text-muted"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
