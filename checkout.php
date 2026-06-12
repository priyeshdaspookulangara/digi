<?php
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout | NexGen Marketplace</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="glass-card" style="max-width: 500px; margin: 50px auto; padding: 30px; text-align: center;">
            <h2 class="neon-text">MLM Activation</h2>

            <?php if ($error): ?>
                <div class="alert error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert success"><?php echo e($success); ?></div>
                <a href="index.php" class="neon-button" style="display:inline-block; margin-top:20px;">Return to Marketplace</a>
            <?php elseif ($has_paid): ?>
                <p>Your account is already active in the hierarchy.</p>
                <a href="index.php" class="neon-button">Go to Marketplace</a>
            <?php else: ?>
                <p>To participate in the 10-level referral program and start earning commissions, please pay the entry fee.</p>
                <div class="price-tag neon-text" style="font-size: 2em; margin: 20px 0;">Rs. 3,000.00</div>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <button type="submit" class="neon-button">PAY & ACTIVATE NOW</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
