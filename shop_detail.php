<?php
require_once 'includes/config.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM shops WHERE id = ?");
$stmt->execute([$id]);
$shop = $stmt->fetch();

if (!$shop) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE shop_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$products = $stmt->fetchAll();

$pageTitle = e($shop['name']) . " | NexGen Marketplace";
$seoTags = [
    'title' => $shop['og_title'] ?: $shop['name'],
    'description' => $shop['og_description'] ?: $shop['description'],
    'image' => $shop['logo'],
    'url' => BASE_URL . "/shop_detail.php?id=" . $shop['id'],
    'keywords' => $shop['meta_keywords']
];
include 'includes/header.php';
?>

<!-- Structured Data -->
<?php echo renderShopJSONLD($shop); ?>

<!-- Shop Hero -->
<div class="shop-hero position-relative mb-5" style="height: 300px;">
    <?php if($shop['type'] !== 'free_listing' && $shop['wallpaper']): ?>
        <img src="<?php echo BASE_URL . '/' . $shop['wallpaper']; ?>" class="w-100 h-100 object-fit-cover opacity-50">
    <?php else: ?>
        <div class="w-100 h-100 bg-dark opacity-50"></div>
    <?php endif; ?>

    <div class="position-absolute bottom-0 start-0 w-100 p-4 p-md-5" style="background: linear-gradient(transparent, rgba(0,0,0,0.8));">
        <div class="container d-flex align-items-end">
            <div class="shop-logo-lg glass-card p-2 bg-dark" style="width: 120px; height: 120px; border-radius: 15px;">
                <?php if($shop['logo']): ?>
                    <img src="<?php echo BASE_URL . '/' . $shop['logo']; ?>" class="w-100 h-100 object-fit-contain rounded" alt="Logo">
                <?php else: ?>
                    <div class="w-100 h-100 d-flex align-items-center justify-content-center opacity-25">
                        <i class="fas fa-store fa-3x"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="ms-4 mb-2">
                <h1 class="display-5 fw-bold neon-text m-0"><?php echo e($shop['name']); ?></h1>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="badge bg-info"><?php echo e($shop['category']); ?></span>
                    <span class="badge bg-secondary opacity-75"><i class="fas fa-location-dot me-1"></i> <?php echo e($shop['locality']); ?>, <?php echo e($shop['city']); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row g-5">
        <div class="col-lg-8">
            <h3 class="neon-text mb-4">Our Products</h3>
            <div class="row g-4">
                <?php if(empty($products)): ?>
                    <div class="col-12 text-center py-5 opacity-50">No products listed by this shop yet.</div>
                <?php endif; ?>
                <?php foreach($products as $p): ?>
                <div class="col-md-6">
                    <div class="glass-card product-card p-3 h-100">
                        <div class="product-img-wrapper" style="height: 200px;">
                            <?php if($p['image']): ?>
                                <img src="<?php echo BASE_URL . '/' . $p['image']; ?>" class="w-100 h-100 object-fit-cover rounded">
                            <?php else: ?>
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-secondary bg-opacity-25 rounded">
                                    <i class="fas fa-box fa-3x opacity-25"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-3">
                            <h5 class="m-0"><?php echo e($p['name']); ?></h5>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <span class="product-price">₹<?php echo number_format($p['price'], 2); ?></span>
                                    <?php if($shop['type'] === 'privilege' && $p['discount_entry']): ?>
                                        <div class="small neon-cyan fw-bold"><?php echo e($p['discount_entry']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <button class="btn btn-sm btn-outline-info rounded-circle add-to-cart-btn" data-id="<?php echo $p['id']; ?>"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="glass-card p-4 sticky-top" style="top: 100px;">
                <h4 class="h5 mb-3 neon-text">About the Shop</h4>
                <p class="opacity-75 small mb-4">
                    <?php echo e($shop['description'] ?: 'This shop provides high-quality products and services to its customers.'); ?>
                </p>

                <?php if($shop['address']): ?>
                <div class="mb-4 pt-3 border-top border-secondary border-opacity-10">
                    <h6 class="small fw-bold text-uppercase opacity-50 mb-2">Location</h6>
                    <p class="small opacity-75 mb-1"><i class="fas fa-map-marker-alt me-2 text-info"></i><?php echo e($shop['address']); ?></p>
                    <p class="small opacity-75 mb-1 ms-4"><?php echo e($shop['locality']); ?>, <?php echo e($shop['city']); ?></p>
                    <p class="small opacity-75 mb-0 ms-4"><?php echo e($shop['district']); ?> - <?php echo e($shop['pincode']); ?></p>
                </div>
                <?php endif; ?>

                <?php if($shop['type'] !== 'free_listing'): ?>
                <h4 class="h5 mb-3 neon-text small text-uppercase">Connect</h4>
                <div class="d-flex gap-3 mb-4">
                    <a href="#" class="glass-btn-circle sm"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="glass-btn-circle sm"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="glass-btn-circle sm"><i class="fab fa-whatsapp"></i></a>
                </div>
                <?php endif; ?>

                <div class="pt-4 border-top border-secondary border-opacity-25">
                    <h4 class="h5 mb-3 neon-text small text-uppercase">Send Enquiry</h4>
                    <form action="submit_enquiry.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                        <input type="hidden" name="shop_id" value="<?php echo $shop['id']; ?>">
                        <div class="mb-3">
                            <input type="text" name="customer_name" class="glass-input small" placeholder="Your Name" required>
                        </div>
                        <div class="mb-3">
                            <textarea name="message" class="glass-input small" rows="3" placeholder="I'm interested in..." required></textarea>
                        </div>
                        <button type="submit" class="neon-button-sm w-100">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
