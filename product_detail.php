<?php
$productId = $_GET['id'] ?? 0;
require_once 'includes/config.php';

// Fetch product with shop details
$stmt = $pdo->prepare("SELECT p.*, s.name as shop_name, s.logo as shop_logo, s.locality as shop_locality FROM products p JOIN shops s ON p.shop_id = s.id WHERE p.id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    // Fallback Mock
    $product = [
        'id' => 0,
        'name' => 'CyberPulse Smartwatch',
        'description' => 'A high-tech smartwatch with health tracking and AI integration.',
        'price' => 12500.00,
        'image' => 'https://images.unsplash.com/photo-1544117519-31a4b719223d?auto=format&fit=crop&q=80&w=400',
        'meta_keywords' => 'smartwatch, ai, tech, health',
        'og_title' => 'CyberPulse Smartwatch | NexGen Marketplace',
        'og_description' => 'Experience the future on your wrist with CyberPulse.',
        'shop_name' => 'TechNova Solutions',
        'shop_locality' => 'Cyber City'
    ];
}

$pageTitle = $product['og_title'] ?: $product['name'] . " | NexGen Marketplace";
$seoTags = [
    'title' => $product['og_title'] ?: $product['name'],
    'description' => $product['og_description'] ?: substr(strip_tags($product['description']), 0, 160),
    'image' => $product['image'],
    'url' => BASE_URL . "/product_detail.php?id=" . $product['id'],
    'keywords' => $product['meta_keywords']
];

require_once 'includes/header.php';

// Handle Enquiry Submission
$enquirySuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_enquiry'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $stmt = $pdo->prepare("INSERT INTO enquiries (shop_id, product_id, customer_name, customer_email, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$product['shop_id'] ?? 0, $product['id'], $_POST['name'], $_POST['email'], $_POST['message']]);
        $enquirySuccess = true;
    }
}
?>

<main class="container mt-5">
    <!-- Structured Data -->
    <?php echo renderProductJSONLD($product, ['name' => $product['shop_name']]); ?>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php" class="text-info">Home</a></li>
            <li class="breadcrumb-item active text-white opacity-50" aria-current="page"><?php echo e($product['name']); ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- Product Images -->
        <div class="col-lg-7">
            <div class="glass-card p-2 sticky-top" style="top: 100px;">
                <div class="main-img-container rounded-4 overflow-hidden shadow-lg">
                    <img src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>" class="w-100 img-fluid">
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-3"><img src="<?php echo e($product['image']); ?>" class="img-fluid rounded border border-info border-opacity-50"></div>
                    <!-- Placeholders for multiple images -->
                    <div class="col-3"><div class="ratio ratio-1x1 bg-dark bg-opacity-25 rounded border border-secondary border-opacity-25"></div></div>
                    <div class="col-3"><div class="ratio ratio-1x1 bg-dark bg-opacity-25 rounded border border-secondary border-opacity-25"></div></div>
                </div>
            </div>
        </div>

        <!-- Product Details -->
        <div class="col-lg-5">
            <div class="product-header mb-4">
                <span class="badge bg-info text-dark mb-2">NEW ARRIVAL</span>
                <h1 class="display-5 fw-bold mb-1"><?php echo e($product['name']); ?></h1>
                <p class="text-info d-flex align-items-center">
                    <i class="fas fa-store me-2"></i> Sold by <?php echo e($product['shop_name']); ?>
                </p>
            </div>

            <div class="price-section mb-4 p-4 glass-card border-info border-opacity-25">
                <div class="d-flex align-items-baseline gap-2">
                    <h2 class="neon-cyan fw-bold mb-0">₹<?php echo number_format($product['price'], 2); ?></h2>
                    <span class="text-white opacity-25 text-decoration-line-through">₹<?php echo number_format($product['price'] * 1.2, 2); ?></span>
                    <span class="badge bg-success small">20% OFF</span>
                </div>
                <p class="small text-white-50 mt-2">Inclusive of all taxes. Exclusive MLM benefits apply.</p>
            </div>

            <div class="actions-section d-grid gap-3 mb-5">
                <button class="btn btn-primary btn-lg rounded-pill shadow-lg py-3 fw-bold add-to-cart-btn" data-id="<?php echo $product['id']; ?>">
                    <i class="fas fa-cart-plus me-2"></i> ADD TO CART
                </button>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-light flex-grow-1 rounded-pill py-2 wishlist-btn" data-id="<?php echo $product['id']; ?>">
                        <i class="far fa-heart me-2"></i> WISHLIST
                    </button>
                    <a href="checkout.php?product_id=<?php echo $product['id']; ?>" class="btn btn-outline-info flex-grow-1 rounded-pill py-2 text-decoration-none text-center">
                        <i class="fas fa-bolt me-2"></i> JOIN MLM
                    </a>
                </div>
            </div>

            <div class="info-accordion" id="productAccordion">
                <div class="glass-card mb-2 border-0 overflow-hidden">
                    <button class="btn btn-link w-100 text-start text-white text-decoration-none py-3 px-4 fw-bold d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#descCollapse">
                        DESCRIPTION <i class="fas fa-chevron-down small opacity-50"></i>
                    </button>
                    <div id="descCollapse" class="collapse show px-4 pb-4 opacity-75">
                        <?php echo nl2br(e($product['description'])); ?>
                    </div>
                </div>

                <div class="glass-card mb-2 border-0 overflow-hidden">
                    <button class="btn btn-link w-100 text-start text-white text-decoration-none py-3 px-4 fw-bold d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#enquiryCollapse">
                        CONTACT SHOP <i class="fas fa-chevron-down small opacity-50"></i>
                    </button>
                    <div id="enquiryCollapse" class="collapse px-4 pb-4">
                        <?php if($enquirySuccess): ?>
                            <div class="alert alert-success bg-success bg-opacity-25 border-success text-white small">Your message has been sent to the shop owner.</div>
                        <?php endif; ?>
                        <form action="product_detail.php?id=<?php echo $product['id']; ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                            <div class="mb-3">
                                <input type="text" name="name" class="form-control glass-input" placeholder="Your Name" required>
                            </div>
                            <div class="mb-3">
                                <input type="email" name="email" class="form-control glass-input" placeholder="Your Email" required>
                            </div>
                            <div class="mb-3">
                                <textarea name="message" class="form-control glass-input" rows="3" placeholder="Message to vendor..." required></textarea>
                            </div>
                            <button type="submit" name="submit_enquiry" class="btn btn-info w-100 rounded-pill">SEND ENQUIRY</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
