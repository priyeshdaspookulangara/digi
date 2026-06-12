<?php
require_once 'includes/config.php';
require_role('shop_owner');
require_once 'includes/mlm_logic.php';

// Fetch active shop for current user
$stmt = $pdo->prepare("SELECT * FROM shops WHERE owner_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$shop = $stmt->fetch();

if (!$shop) {
    // Create a default shop if none exists for the owner
    $stmt = $pdo->prepare("INSERT INTO shops (owner_id, name) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['username'] . "'s Shop"]);
    $shop_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    $shop = $stmt->fetch();
}

// Handle Offer Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_offer'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    $stmt = $pdo->prepare("INSERT INTO offers (shop_id, title, description, discount_percent) VALUES (?, ?, ?, ?)");
    $stmt->execute([$shop['id'], $_POST['offer_title'], $_POST['offer_desc'], $_POST['discount']]);
}

// Fetch Enquiries
$stmt = $pdo->prepare("SELECT e.*, p.name as product_name FROM enquiries e LEFT JOIN products p ON e.product_id = p.id WHERE e.shop_id = ? ORDER BY e.created_at DESC");
$stmt->execute([$shop['id']]);
$enquiries = $stmt->fetchAll();

// Fetch Offers
$stmt = $pdo->prepare("SELECT * FROM offers WHERE shop_id = ? ORDER BY created_at DESC");
$stmt->execute([$shop['id']]);
$offers = $stmt->fetchAll();

$csrf_token = get_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Portal | <?php echo htmlspecialchars($shop['name']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1 class="neon-text">Shop Management Dashboard</h1>

        <div class="dashboard-grid">
            <!-- Product Management -->
            <section class="glass-card">
                <h2>Add/Edit Product</h2>
                <form action="process_product.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Product Name" class="glass-input" required>
                        <input type="number" name="price" placeholder="Price (Rs.)" class="glass-input" required>
                    </div>
                    <textarea name="description" placeholder="Description" class="glass-input" required></textarea>

                    <hr class="neon-line">
                    <h3>SEO & Social Marketing</h3>
                    <input type="text" name="meta_keywords" placeholder="Keywords (comma separated)" class="glass-input">
                    <input type="text" name="og_title" placeholder="OG Title (Social Preview)" class="glass-input">
                    <textarea name="og_description" placeholder="OG Description" class="glass-input"></textarea>

                    <div class="form-group">
                        <label>Product Image <input type="file" name="product_image" class="glass-input"></label>
                        <label>Product Video <input type="file" name="product_video" class="glass-input"></label>
                    </div>

                    <label><input type="checkbox" name="is_featured"> Featured Product</label>

                    <button type="submit" class="neon-button">SAVE PRODUCT</button>
                </form>
            </section>

            <!-- Offer Management -->
            <section class="glass-card">
                <h2>Manage Active Offers</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="add_offer" value="1">
                    <input type="text" name="offer_title" placeholder="Offer Title" class="glass-input" required>
                    <textarea name="offer_desc" placeholder="Offer Description" class="glass-input"></textarea>
                    <input type="number" name="discount" placeholder="Discount %" class="glass-input">
                    <button type="submit" class="neon-button">CREATE OFFER</button>
                </form>
                <div class="offer-list" style="margin-top: 20px;">
                    <?php foreach($offers as $offer): ?>
                    <div class="enquiry-item glass-card" style="margin-bottom: 10px;">
                        <p><strong><?php echo e($offer['title']); ?></strong> (<?php echo e($offer['discount_percent']); ?>%)</p>
                        <p><?php echo e($offer['description']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Enquiry System -->
            <section class="glass-card">
                <h2>Customer Enquiries</h2>
                <div class="enquiry-list">
                    <?php if(empty($enquiries)): ?>
                        <p>No enquiries yet.</p>
                    <?php endif; ?>
                    <?php foreach($enquiries as $enq): ?>
                    <div class="enquiry-item glass-card">
                        <p><strong>From:</strong> <?php echo e($enq['customer_name']); ?> (<?php echo e($enq['customer_email']); ?>)</p>
                        <p><strong>Product:</strong> <?php echo e($enq['product_name'] ?? 'General'); ?></p>
                        <p>"<?php echo e($enq['message']); ?>"</p>
                        <button class="glass-button">Reply</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
