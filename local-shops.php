<?php
require_once 'includes/config.php';

$place = $_GET['place'] ?? '';
if (!$place) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM shops WHERE locality = ? ORDER BY type = 'privilege' DESC, type = 'classic' DESC, name ASC");
$stmt->execute([$place]);
$shops = $stmt->fetchAll();

$pageTitle = "Shops in " . e($place) . " | NexGen Marketplace";
include 'includes/header.php';
?>

<div class="container my-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-info">Home</a></li>
            <li class="breadcrumb-item active text-white">Shops in <?php echo e($place); ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="neon-text m-0">Local Shops & Stores</h1>
        <span class="badge bg-info rounded-pill px-3 py-2">Location: <?php echo e($place); ?></span>
    </div>

    <div class="row g-4">
        <?php if(empty($shops)): ?>
            <div class="col-12 text-center py-5">
                <div class="glass-card p-5">
                    <i class="fas fa-store-slash fa-4x mb-4 opacity-25"></i>
                    <h3>No shops found in this locality.</h3>
                    <p class="opacity-50">Try exploring other nearby areas.</p>
                    <a href="index.php" class="neon-button-sm mt-3">Back to Marketplace</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach($shops as $shop): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-card shop-card h-100 p-0 overflow-hidden">
                        <?php if($shop['type'] !== 'free_listing' && $shop['wallpaper']): ?>
                            <div class="shop-header-img" style="height: 120px;">
                                <img src="<?php echo BASE_URL . '/' . $shop['wallpaper']; ?>" class="w-100 h-100 object-fit-cover opacity-50">
                            </div>
                        <?php else: ?>
                            <div class="shop-header-img bg-secondary bg-opacity-25" style="height: 120px;"></div>
                        <?php endif; ?>

                        <div class="p-4" style="margin-top: -50px;">
                            <div class="d-flex align-items-end mb-3">
                                <div class="shop-logo-wrapper glass-card p-1 bg-dark" style="width: 80px; height: 80px; border-radius: 12px;">
                                    <?php if($shop['logo']): ?>
                                        <img src="<?php echo BASE_URL . '/' . $shop['logo']; ?>" class="w-100 h-100 object-fit-contain rounded" alt="Logo">
                                    <?php else: ?>
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center opacity-25">
                                            <i class="fas fa-store fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ms-3 mb-1">
                                    <?php if($shop['type'] === 'privilege'): ?>
                                        <span class="badge bg-warning text-dark mb-1">PRIVILEGE</span>
                                    <?php elseif($shop['type'] === 'classic'): ?>
                                        <span class="badge bg-info mb-1">CLASSIC</span>
                                    <?php endif; ?>
                                    <h5 class="m-0"><?php echo e($shop['name']); ?></h5>
                                </div>
                            </div>

                            <p class="small opacity-75 mb-4 line-clamp-3">
                                <?php echo e($shop['description'] ?: 'No description available for this shop.'); ?>
                            </p>

                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <span class="small opacity-50"><i class="fas fa-tag me-1"></i> <?php echo e($shop['category']); ?></span>
                                <a href="shop_detail.php?id=<?php echo $shop['id']; ?>" class="neon-button-sm">Visit Shop</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
