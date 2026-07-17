<?php
require_once 'includes/config.php';

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header("Location: portfolios.php");
    exit;
}

// Fetch portfolio with category name
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name
    FROM portfolios p
    JOIN professional_categories c ON p.category_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$portfolio = $stmt->fetch();

if (!$portfolio) {
    die("Professional portfolio not found.");
}

$pageTitle = $portfolio['professional_name'] . " - " . $portfolio['title'] . " | NexGen Marketplace";
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row g-4">
        <!-- Profile Column -->
        <div class="col-lg-4">
            <div class="glass-card p-4 text-center bg-white shadow-sm border-0 mb-4 sticky-top" style="top: 100px;">
                <img src="<?php echo $portfolio['image'] ?: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=200&h=200&fit=crop'; ?>" alt="<?php echo e($portfolio['professional_name']); ?>" class="rounded-circle object-fit-cover shadow border mb-3" style="width: 140px; height: 140px;">

                <h3 class="fw-bold text-dark m-0"><?php echo e($portfolio['professional_name']); ?></h3>
                <p class="text-primary fw-semibold mb-2"><?php echo e($portfolio['title']); ?></p>
                <span class="badge bg-soft-primary text-primary px-3 py-2 mb-4" style="font-size: 0.75rem;"><?php echo e($portfolio['category_name']); ?></span>

                <div class="border-top pt-3 text-start">
                    <div class="d-flex align-items-center mb-3 text-dark">
                        <i class="fas fa-briefcase me-3 text-muted" style="width: 20px;"></i>
                        <div>
                            <small class="text-muted d-block uppercase" style="font-size: 0.65rem;">EXPERIENCE</small>
                            <strong><?php echo $portfolio['experience_years']; ?> Years</strong>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mb-3 text-dark">
                        <i class="fas fa-map-marker-alt me-3 text-muted" style="width: 20px;"></i>
                        <div>
                            <small class="text-muted d-block uppercase" style="font-size: 0.65rem;">LOCATION</small>
                            <strong><?php echo e($portfolio['locality'] ?: 'Thrissur'); ?></strong>
                        </div>
                    </div>

                    <?php if(!empty($portfolio['contact_email'])): ?>
                    <div class="d-flex align-items-center mb-3 text-dark">
                        <i class="fas fa-envelope me-3 text-muted" style="width: 20px;"></i>
                        <div class="overflow-hidden">
                            <small class="text-muted d-block uppercase" style="font-size: 0.65rem;">EMAIL ADDRESS</small>
                            <a href="mailto:<?php echo e($portfolio['contact_email']); ?>" class="text-dark fw-bold text-truncate d-block text-decoration-none"><?php echo e($portfolio['contact_email']); ?></a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if(!empty($portfolio['contact_phone'])): ?>
                    <div class="d-flex align-items-center text-dark">
                        <i class="fas fa-phone-alt me-3 text-muted" style="width: 20px;"></i>
                        <div>
                            <small class="text-muted d-block uppercase" style="font-size: 0.65rem;">PHONE / WHATSAPP</small>
                            <a href="tel:<?php echo e($portfolio['contact_phone']); ?>" class="text-dark fw-bold text-decoration-none"><?php echo e($portfolio['contact_phone']); ?></a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="mt-4 border-top pt-3">
                    <?php if(!empty($portfolio['contact_phone'])): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $portfolio['contact_phone']); ?>" class="btn btn-success w-100 fw-bold py-2 mb-2" target="_blank">
                            <i class="fab fa-whatsapp me-2"></i> CONTACT ON WHATSAPP
                        </a>
                    <?php endif; ?>
                    <?php if(!empty($portfolio['contact_email'])): ?>
                        <a href="mailto:<?php echo e($portfolio['contact_email']); ?>" class="btn btn-outline-primary w-100 fw-bold py-2">
                            <i class="far fa-envelope me-2"></i> SEND EMAIL
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Details Column -->
        <div class="col-lg-8">
            <!-- About Section -->
            <div class="card shadow-sm border-0 p-4 mb-4 bg-white">
                <h4 class="fw-bold text-dark mb-3 border-bottom pb-2">About Me</h4>
                <p class="text-muted lead" style="font-size: 1rem; line-height: 1.7; white-space: pre-line;">
                    <?php echo e($portfolio['description'] ?: 'This professional has not provided a bio yet.'); ?>
                </p>
            </div>

            <!-- Skills & Expertise -->
            <?php if(!empty($portfolio['skills'])): ?>
            <div class="card shadow-sm border-0 p-4 mb-4 bg-white">
                <h4 class="fw-bold text-dark mb-3 border-bottom pb-2">Skills & Expertise</h4>
                <div class="d-flex flex-wrap gap-2 pt-2">
                    <?php foreach(explode(',', $portfolio['skills']) as $skill): ?>
                        <span class="badge bg-light text-dark border px-3 py-2 fw-normal" style="font-size: 0.85rem;">
                            <i class="fas fa-check text-primary me-2"></i> <?php echo e(trim($skill)); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Services Offered -->
            <?php if(!empty($portfolio['services'])): ?>
            <div class="card shadow-sm border-0 p-4 bg-white">
                <h4 class="fw-bold text-dark mb-3 border-bottom pb-2">Services Offered</h4>
                <div class="row g-3 pt-2">
                    <?php foreach(explode(',', $portfolio['services']) as $service): ?>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded d-flex align-items-center">
                                <i class="fas fa-concierge-bell text-primary me-3 fa-lg"></i>
                                <span class="fw-bold text-dark"><?php echo e(trim($service)); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
