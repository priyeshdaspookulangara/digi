<?php
$pageTitle = "Checkout & Activation | NexGen Marketplace";
require_once 'includes/config.php';
require_once 'includes/mlm_logic.php';

require_login();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Check if user has already paid entry fee
$stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND type = 'entry_fee'");
$stmt->execute([$user_id]);
$has_paid = $stmt->fetchColumn() > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$has_paid) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }

    try {
        $pdo->beginTransaction();

        // Execute MLM Commission Distribution
        distributeEntryFee($pdo, $user_id);

        $pdo->commit();
        $success = "Your account has been activated! Rs. 3,000.00 fee distributed to the network.";
        $has_paid = true;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Transaction failed: " . $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="glass-card p-5 text-center">
                <div class="activation-icon mb-4">
                    <i class="fas fa-gem fa-4x neon-cyan"></i>
                </div>
                <h2 class="neon-text mb-4">MLM Activation</h2>

                <?php if ($error): ?>
                    <div class="alert alert-danger bg-danger bg-opacity-25 border-danger text-white mb-4">
                        <?php echo e($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success bg-success bg-opacity-25 border-success text-white mb-4 p-4">
                        <i class="fas fa-check-circle fa-2x mb-2 d-block"></i>
                        <?php echo e($success); ?>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="index.php" class="btn btn-primary btn-lg rounded-pill">Start Shopping</a>
                        <a href="dashboard.php" class="btn btn-outline-info rounded-pill">View My Tree</a>
                    </div>
                <?php elseif ($has_paid): ?>
                    <div class="p-4 bg-info bg-opacity-10 rounded-4 mb-4">
                        <i class="fas fa-certificate neon-cyan mb-2"></i>
                        <p class="mb-0">Your account is fully activated in the 10-level hierarchy.</p>
                    </div>
                    <a href="index.php" class="btn btn-primary btn-lg rounded-pill w-100">Continue to Marketplace</a>
                <?php else: ?>
                    <p class="opacity-75 mb-4">Secure your spot in the network and unlock 10 levels of commissions. A one-time activation fee is required to join the ecosystem.</p>

                    <div class="price-display mb-5">
                        <span class="text-uppercase small fw-bold opacity-50 d-block">Activation Fee</span>
                        <h1 class="display-3 fw-bold neon-cyan mb-0">₹3,000</h1>
                    </div>

                    <div class="benefits-list text-start mb-5 opacity-75">
                        <div class="d-flex mb-2"><i class="fas fa-check-circle text-info me-3 mt-1"></i> <span>Access to 10-Level Referral Hierarchy</span></div>
                        <div class="d-flex mb-2"><i class="fas fa-check-circle text-info me-3 mt-1"></i> <span>Automated "Basic Rebirth ID" Generation</span></div>
                        <div class="d-flex mb-2"><i class="fas fa-check-circle text-info me-3 mt-1"></i> <span>Real-time Royalty Chamber Tracking</span></div>
                        <div class="d-flex mb-2"><i class="fas fa-check-circle text-info me-3 mt-1"></i> <span>Exclusive Merchant Partnership Rates</span></div>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill w-100 py-3 fw-bold shadow-lg">
                            CONFIRM & PAY NOW
                        </button>
                    </form>
                    <p class="small text-white-50 mt-4"><i class="fas fa-shield-alt me-1"></i> Secure payment processing. No hidden recurring fees.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
