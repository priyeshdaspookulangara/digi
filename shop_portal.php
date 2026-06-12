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
<?php
$pageTitle = "Shop Portal | " . htmlspecialchars($shop['name']);
include 'includes/header.php';
?>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="neon-text">Shop Management</h1>
            <div class="badge bg-info text-dark p-2"><?php echo e($shop['name']); ?></div>
        </div>

        <div class="row g-4">
        <div class="row g-4 mb-5">
            <!-- Shop Configuration -->
            <div class="col-12">
                <div class="glass-card p-4">
                    <h2 class="h4 mb-4"><i class="fas fa-store me-2 neon-cyan"></i>Shop Identity & Branding</h2>
                    <form action="process_shop.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="existing_logo" value="<?php echo e($shop['logo']); ?>">
                        <input type="hidden" name="existing_wallpaper" value="<?php echo e($shop['wallpaper']); ?>">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small opacity-75">Shop Name</label>
                                <input type="text" name="name" class="glass-input" value="<?php echo e($shop['name']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small opacity-75">Category</label>
                                <input type="text" name="category" class="glass-input" value="<?php echo e($shop['category']); ?>" placeholder="Electronics, Fashion, etc.">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small opacity-75">Locality</label>
                                <input type="text" name="locality" class="glass-input" value="<?php echo e($shop['locality']); ?>" placeholder="City, State">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small opacity-75">Shop Logo</label>
                                <input type="file" name="shop_logo" class="glass-input">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small opacity-75">Shop Wallpaper/Cover</label>
                                <input type="file" name="shop_wallpaper" class="glass-input">
                            </div>
                            <div class="col-12">
                                <label class="form-label small opacity-75">Shop Description</label>
                                <textarea name="description" class="glass-input" rows="2"><?php echo e($shop['description']); ?></textarea>
                            </div>

                            <div class="col-12">
                                <h3 class="h6 mt-3 neon-text small text-uppercase fw-bold">Shop SEO & Social Preview</h3>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <input type="text" name="meta_keywords" placeholder="Keywords" class="glass-input" value="<?php echo e($shop['meta_keywords']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="og_title" placeholder="OG Title" class="glass-input" value="<?php echo e($shop['og_title']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="og_description" placeholder="OG Description" class="glass-input" value="<?php echo e($shop['og_description']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="neon-button mt-4">Update Shop Settings</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Product Management -->
            <div class="col-lg-8">
                <div class="glass-card p-4">
                    <h2 class="h4 mb-4"><i class="fas fa-plus-circle me-2 neon-cyan"></i>Add New Product</h2>
                    <form action="process_product.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label opacity-75 small">Product Name</label>
                                <input type="text" name="name" class="glass-input" placeholder="e.g. CyberPulse Smartwatch" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label opacity-75 small">Price (Rs.)</label>
                                <input type="number" name="price" class="glass-input" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label opacity-75 small">Description</label>
                            <textarea name="description" class="glass-input" rows="3" placeholder="Describe your product highlights..." required></textarea>
                        </div>

                        <div class="mb-4">
                            <h3 class="h5 mb-3 neon-text small text-uppercase fw-bold">SEO & Social Marketing</h3>
                            <div class="row g-3">
                                <div class="col-12">
                                    <input type="text" name="meta_keywords" placeholder="Keywords (comma separated)" class="glass-input">
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="og_title" placeholder="OG Title (Social Preview)" class="glass-input">
                                </div>
                                <div class="col-md-6">
                                    <textarea name="og_description" placeholder="OG Description" class="glass-input" rows="1"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label opacity-75 small">Product Image</label>
                                <input type="file" name="product_image" class="glass-input">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label opacity-75 small">Product Video</label>
                                <input type="file" name="product_video" class="glass-input">
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="featuredSwitch">
                                <label class="form-check-label" for="featuredSwitch">Mark as Featured Product</label>
                            </div>
                        </div>

                        <button type="submit" class="neon-button w-100">SAVE PRODUCT</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Offer Management -->
                <div class="glass-card p-4 mb-4">
                    <h2 class="h4 mb-4"><i class="fas fa-tag me-2 neon-purple"></i>Manage Offers</h2>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="add_offer" value="1">
                        <div class="mb-3">
                            <input type="text" name="offer_title" placeholder="Offer Title" class="glass-input" required>
                        </div>
                        <div class="mb-3">
                            <textarea name="offer_desc" placeholder="Offer Description" class="glass-input" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <input type="number" name="discount" placeholder="Discount %" class="glass-input">
                        </div>
                        <button type="submit" class="neon-button w-100 py-2">CREATE OFFER</button>
                    </form>

                    <div class="mt-4 pt-3 border-top border-secondary">
                        <h4 class="small text-uppercase opacity-50 mb-3">Active Offers</h4>
                        <?php foreach($offers as $offer): ?>
                        <div class="glass-card p-2 mb-2 small border-0 bg-white-10">
                            <div class="d-flex justify-content-between">
                                <strong><?php echo e($offer['title']); ?></strong>
                                <span class="badge bg-success"><?php echo e($offer['discount_percent']); ?>% OFF</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Enquiry System -->
                <div class="glass-card p-4">
                    <h2 class="h4 mb-4"><i class="fas fa-envelope me-2 neon-cyan"></i>Customer Enquiries</h2>
                    <div class="enquiry-list" style="max-height: 400px; overflow-y: auto;">
                        <?php if(empty($enquiries)): ?>
                            <p class="text-center opacity-50 my-5">No enquiries yet.</p>
                        <?php endif; ?>
                        <?php foreach($enquiries as $enq): ?>
                        <div class="enquiry-item p-3 mb-3 glass-card border-0 bg-white-10">
                            <p class="mb-1 small"><strong>From:</strong> <?php echo e($enq['customer_name']); ?></p>
                            <p class="mb-2 small"><strong>Product:</strong> <span class="neon-cyan"><?php echo e($enq['product_name'] ?? 'General'); ?></span></p>
                            <p class="mb-3 small italic">"<?php echo e($enq['message']); ?>"</p>
                            <button class="btn btn-sm neon-button-sm w-100">Reply</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
