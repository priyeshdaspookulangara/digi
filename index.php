<?php
require_once 'includes/config.php';
require_once 'includes/seo_helper.php';

// Search and Filter Logic
$search = $_GET['search'] ?? '';
$locality = $_GET['locality'] ?? '';
$category = $_GET['category'] ?? '';

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
if ($category) {
    $query .= " AND s.category = ?";
    $params[] = $category;
}

$query .= " ORDER BY p.is_featured DESC, p.created_at DESC LIMIT 20";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get unique localities and categories for filters
$localities = $pdo->query("SELECT DISTINCT locality FROM shops WHERE locality IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
$categories = $pdo->query("SELECT DISTINCT category FROM shops WHERE category IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);

// Mock fallback if DB is empty
if (empty($products)) {
    $products = [
        [
            'id' => 1,
            'name' => 'CyberPulse Smartwatch',
            'description' => 'Experience the future with AI-driven health tracking.',
            'price' => 12500.00,
            'image' => 'https://images.unsplash.com/photo-1544117519-31a4b719223d?auto=format&fit=crop&q=80&w=400',
            'is_featured' => 1
        ],
        [
            'id' => 2,
            'name' => 'Neon Drift Headphones',
            'description' => 'Sonic precision meets cyberpunk aesthetics.',
            'price' => 8900.00,
            'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&q=80&w=400',
            'is_featured' => 0
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexGen Marketplace | Premium Integrated MLM</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php
    echo renderMetaTags("NexGen Marketplace", "Modern MLM-based high-tech marketplace.", "", BASE_URL, "mlm, e-commerce, tech");
    // Static Shop JSON-LD for the marketplace homepage
    echo renderShopJSONLD([
        'id' => 1,
        'name' => 'NexGen Marketplace',
        'logo' => BASE_URL . '/assets/img/logo.png',
        'description' => 'The leading integrated MLM-based high-tech marketplace.',
        'locality' => 'Global Digital Hub'
    ]);
    ?>
</head>
<body>
    <nav class="navbar glass-card">
        <div class="logo neon-text">NEXGEN</div>
        <div class="nav-links">
            <a href="index.php">Marketplace</a>
            <?php if (is_logged_in()): ?>
                <a href="shop_portal.php">Merchant Portal</a>
                <a href="checkout.php">Activate MLM</a>
                <span class="user-greeting">Welcome, <?php echo e($_SESSION['username']); ?></span>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php" class="neon-button">Join Now</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <header class="hero">
            <h1 class="neon-text">The Future of Commerce is <span class="neon-cyan">Connected</span></h1>
            <p>Shop top-tier tech and earn through our 10-level referral ecosystem.</p>

            <div class="search-box glass-card" style="margin-top: 30px; padding: 20px;">
                <form action="index.php" method="GET" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <input type="text" name="search" placeholder="Search products..." value="<?php echo e($search); ?>" class="glass-input" style="flex: 2; min-width: 200px;">
                    <select name="locality" class="glass-input" style="flex: 1; min-width: 150px;">
                        <option value="">All Localities</option>
                        <?php foreach($localities as $loc): ?>
                            <option value="<?php echo e($loc); ?>" <?php echo $locality == $loc ? 'selected' : ''; ?>><?php echo e($loc); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="category" class="glass-input" style="flex: 1; min-width: 150px;">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo e($cat); ?>" <?php echo $category == $cat ? 'selected' : ''; ?>><?php echo e($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="neon-button">SEARCH</button>
                </form>
            </div>
        </header>

        <section class="marketplace-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card glass-card <?php echo $product['is_featured'] ? 'featured' : ''; ?>">
                <div class="product-img">
                    <img src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                </div>
                <h3><?php echo e($product['name']); ?></h3>
                <p><?php echo e(substr($product['description'], 0, 80)); ?>...</p>
                <div class="price-tag neon-text">Rs. <?php echo number_format($product['price'], 2); ?></div>
                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="glass-button">VIEW DETAILS</a>
            </div>
            <?php endforeach; ?>
        </section>
    </div>

    <footer class="glass-card">
        <p>&copy; 2023 NexGen Integrated Marketplace & MLM. Built with Precision.</p>
    </footer>
</body>
</html>
