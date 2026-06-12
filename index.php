<?php
$pageTitle = "NexGen Marketplace | High-Tech MLM Ecosystem";
require_once 'includes/header.php';

// Search and Filter Logic
$search = $_GET['search'] ?? '';
$locality = $_GET['locality'] ?? '';
$category_filter = $_GET['category'] ?? '';

$query = "SELECT p.*, s.locality, s.category FROM products p JOIN shops s ON p.shop_id = s.id WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($locality) {
    $query .= " AND s.locality = ?";
    $params[] = $locality;
}
if ($category_filter) {
    $query .= " AND s.category = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY p.is_featured DESC, p.created_at DESC LIMIT 20";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get unique categories for the circle icons
$categories = $pdo->query("SELECT DISTINCT category FROM shops WHERE category IS NOT NULL AND category != ''")->fetchAll(PDO::FETCH_COLUMN);
if (empty($categories)) {
    $categories = ['Electronics', 'Fashion', 'Home', 'Beauty', 'Sports', 'Toys'];
}

// Mock fallback for products
if (empty($products)) {
    $products = [
        ['id' => 1, 'name' => 'CyberPulse Smartwatch', 'price' => 12500, 'image' => 'https://images.unsplash.com/photo-1544117519-31a4b719223d?w=600', 'is_featured' => 1, 'description' => 'AI health tracking.'],
        ['id' => 2, 'name' => 'Neon Drift Headphones', 'price' => 8900, 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600', 'is_featured' => 0, 'description' => 'Cyberpunk aesthetics.'],
        ['id' => 3, 'name' => 'Quantum VR Headset', 'price' => 45000, 'image' => 'https://images.unsplash.com/photo-1622979135225-d2ba269cf1ac?w=600', 'is_featured' => 1, 'description' => 'Next-gen immersion.'],
        ['id' => 4, 'name' => 'AeroDrone X Pro', 'price' => 72000, 'image' => 'https://images.unsplash.com/photo-1473968512647-3e44a224fe8f?w=600', 'is_featured' => 0, 'description' => '4K stability.']
    ];
}
?>

<main class="container mt-4">
    <!-- Hero Section / Promotional Banner -->
    <section class="hero-banner mb-5">
        <div class="glass-card p-0 overflow-hidden position-relative" style="height: 450px;">
            <img src="https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&q=80&w=1200" alt="Hero" class="w-100 h-100 object-fit-cover">
            <div class="position-absolute top-0 start-0 w-100 h-100 hero-overlay d-flex align-items-center">
                <div class="container px-5">
                    <h1 class="display-3 fw-bold neon-text mb-3">UP TO <span class="neon-cyan">50% OFF</span></h1>
                    <p class="h3 mb-4 fw-light opacity-75">On Next-Gen Hardware & Peripherals</p>
                    <a href="#" class="btn btn-lg neon-button px-5">SHOP NOW</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Category Circles -->
    <section class="categories-section mb-5">
        <h4 class="section-title">Browse Categories</h4>
        <div class="category-scroll d-flex flex-wrap">
            <?php
            $icons = ['Electronics' => 'fas fa-plug', 'Fashion' => 'fas fa-tshirt', 'Home' => 'fas fa-home', 'Beauty' => 'fas fa-magic', 'Sports' => 'fas fa-running', 'Toys' => 'fas fa-gamepad'];
            foreach ($categories as $cat):
                $icon = $icons[$cat] ?? 'fas fa-th-large';
            ?>
            <div class="category-item text-center" style="width: 120px;" onclick="window.location.href='index.php?category=<?php echo urlencode($cat); ?>'">
                <div class="category-circle">
                    <i class="<?php echo $icon; ?>"></i>
                </div>
                <div class="category-name"><?php echo e($cat); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Filter & Search Summary (Mobile/Active Filters) -->
    <?php if ($search || $category_filter || $locality): ?>
    <div class="d-flex gap-2 mb-4">
        <?php if($search): ?><span class="badge rounded-pill glass-card border-info">Search: <?php echo e($search); ?> <a href="index.php" class="text-white ms-1 text-decoration-none">&times;</a></span><?php endif; ?>
        <?php if($category_filter): ?><span class="badge rounded-pill glass-card border-info">Category: <?php echo e($category_filter); ?> <a href="index.php" class="text-white ms-1 text-decoration-none">&times;</a></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Product Showcase -->
    <section class="products-section">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <h4 class="section-title mb-0">Deals of the Day</h4>
            <a href="#" class="text-info text-decoration-none small">View All <i class="fas fa-chevron-right ms-1"></i></a>
        </div>

        <div class="row g-4">
            <?php foreach ($products as $product): ?>
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="glass-card product-card p-3">
                    <div class="product-img-wrapper">
                        <?php if($product['is_featured']): ?>
                            <span class="badge bg-info position-absolute top-0 start-0 m-2 z-3 shadow">FEATURED</span>
                        <?php endif; ?>
                        <button class="wishlist-btn" data-id="<?php echo $product['id']; ?>"><i class="far fa-heart"></i></button>
                        <img src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                    </div>
                    <div class="product-info mt-2">
                        <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="text-white text-decoration-none">
                            <h5 class="product-title"><?php echo e($product['name']); ?></h5>
                        </a>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="product-price">₹<?php echo number_format($product['price'], 2); ?></span>
                            <button class="btn btn-sm btn-outline-info rounded-circle add-to-cart-btn" data-id="<?php echo $product['id']; ?>"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- MLM Benefits Promo -->
    <section class="mlm-promo mt-5 py-5">
        <div class="glass-card p-5 text-center border-info">
            <h2 class="neon-text mb-4">EARN WHILE YOU SHOP</h2>
            <p class="lead mb-4 mx-auto" style="max-width: 800px;">Join our 10-level referral ecosystem and unlock massive commissions. Transform your marketplace experience into a wealth-building journey.</p>
            <div class="row g-4 mt-2">
                <div class="col-md-4">
                    <div class="p-3 border-end border-secondary border-opacity-25">
                        <h1 class="neon-cyan">10</h1>
                        <p class="text-uppercase small fw-bold">Benefit Levels</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border-end border-secondary border-opacity-25">
                        <h1 class="neon-cyan">₹3,000</h1>
                        <p class="text-uppercase small fw-bold">One-Time Activation</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3">
                        <h1 class="neon-cyan">∞</h1>
                        <p class="text-uppercase small fw-bold">Rebirth Potential</p>
                    </div>
                </div>
            </div>
            <a href="register.php" class="btn btn-primary btn-lg rounded-pill px-5 mt-5">GET STARTED NOW</a>
        </div>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>
