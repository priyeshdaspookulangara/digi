<?php
require_once 'includes/config.php';
require_once 'includes/seo_helper.php';

$productId = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT p.*, s.name as shop_name, s.logo as shop_logo FROM products p JOIN shops s ON p.shop_id = s.id WHERE p.id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

// Handle Enquiry Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_enquiry'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    $stmt = $pdo->prepare("INSERT INTO enquiries (shop_id, product_id, customer_name, customer_email, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$product['shop_id'], $product['id'], $_POST['name'], $_POST['email'], $_POST['message']]);
    echo "<script>alert('Enquiry sent successfully!');</script>";
}

if (!$product) {
    // Fallback if not found
    $product = [
        'id' => 0,
        'name' => 'CyberPulse Smartwatch',
        'description' => 'A high-tech smartwatch with health tracking and AI integration.',
        'price' => 12500.00,
        'image' => 'https://images.unsplash.com/photo-1544117519-31a4b719223d?auto=format&fit=crop&q=80&w=400',
        'meta_keywords' => 'smartwatch, ai, tech, health',
        'og_title' => 'CyberPulse Smartwatch | NexGen Marketplace',
        'og_description' => 'Experience the future on your wrist with CyberPulse.',
        'shop_name' => 'TechNova Solutions'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($product['og_title']); ?></title>
    <?php
    echo renderMetaTags($product['og_title'], $product['og_description'], $product['image'], BASE_URL . "/product_detail.php?id=" . $product['id'], $product['meta_keywords']);
    echo renderProductJSONLD($product, ['name' => $product['shop_name']]);
    ?>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar glass-card">
        <div class="logo neon-text"><a href="index.php" style="text-decoration:none; color:inherit;">NEXGEN</a></div>
        <div class="nav-links">
            <a href="index.php">Marketplace</a>
            <?php if (is_logged_in()): ?>
                <a href="shop_portal.php">Merchant Portal</a>
                <span class="user-greeting">Welcome, <?php echo e($_SESSION['username']); ?></span>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <div class="glass-card detail-container" style="margin-top: 30px; padding: 40px;">
            <div class="detail-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                <div class="product-image-large">
                    <img src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>" style="width:100%; border-radius: 15px; box-shadow: 0 0 20px var(--neon-cyan);">
                </div>
                <div class="product-info">
                    <h1 class="neon-text"><?php echo e($product['name']); ?></h1>
                    <p class="shop-name">Sold by: <strong><?php echo e($product['shop_name']); ?></strong></p>
                    <p class="description" style="margin: 20px 0; line-height: 1.6; opacity: 0.9;"><?php echo e($product['description']); ?></p>
                    <div class="price-tag neon-text" style="font-size: 2.5em; margin-bottom: 30px;">Rs. <?php echo number_format($product['price'], 2); ?></div>

                    <a href="checkout.php" class="neon-button" style="text-decoration:none; display:inline-block; padding: 15px 40px;">ACTIVATE & BUY</a>
                    <button class="glass-button" style="margin-left: 10px;">ADD TO WISHLIST</button>

                    <div class="enquiry-form glass-card" style="margin-top: 40px; padding: 20px;">
                        <h3>Send Enquiry</h3>
                        <form action="product_detail.php?id=<?php echo $product['id']; ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                            <input type="text" name="name" placeholder="Your Name" class="glass-input" required>
                            <input type="email" name="email" placeholder="Your Email" class="glass-input" required>
                            <textarea name="message" placeholder="How can we help?" class="glass-input" required></textarea>
                            <button type="submit" name="submit_enquiry" class="neon-button" style="width:100%;">SEND MESSAGE</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
