<?php
require_once '../includes/config.php';
require_role('admin');
require_once '../includes/mlm_logic.php';

// Dynamic stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type = 'entry_fee'")->fetchColumn() ?: 0;
$total_commissions = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type = 'commission'")->fetchColumn() ?: 0;

$recent_transactions = $pdo->query("SELECT t.*, u.username FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 10")->fetchAll();

$entry_fee = getMlmSetting($pdo, 'entry_fee', 3000.00);
$level_comm = getMlmSetting($pdo, 'level_commission', 100.00);
?>
<?php
$pageTitle = "Admin Dashboard | NexGen Marketplace";
include '../includes/header.php';
?>

    <div class="container my-5">
        <h1 class="neon-text mb-4">Central Admin Oversight</h1>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="glass-card p-4 text-center">
                    <h3 class="h6 text-uppercase opacity-50">Total Users</h3>
                    <p class="display-5 fw-bold neon-cyan mb-0"><?php echo $total_users; ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card p-4 text-center">
                    <h3 class="h6 text-uppercase opacity-50">Total Revenue</h3>
                    <p class="display-6 fw-bold neon-purple mb-0">Rs. <?php echo number_format($total_revenue, 2); ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card p-4 text-center">
                    <h3 class="h6 text-uppercase opacity-50">Commissions Paid</h3>
                    <p class="display-6 fw-bold neon-text mb-0">Rs. <?php echo number_format($total_commissions, 2); ?></p>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="glass-card p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="h5 m-0 neon-text">Marketing & Analytics</h3>
                        <p class="small opacity-50 mb-0">Manage sliders, banners, and track campaign performance.</p>
                    </div>
                    <a href="marketing.php" class="neon-button">Manage Marketing</a>
                </div>
            </div>
            <div class="col-12 mt-4">
                <div class="glass-card p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="h5 m-0 neon-text">Shop & Category Control</h3>
                        <p class="small opacity-50 mb-0">Define shop types (Privilege/Classic/Free) and manage marketplace categories.</p>
                    </div>
                    <a href="manage_shops.php" class="neon-button purple">Manage Shops</a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <section class="glass-card p-4">
                    <h2 class="h5 mb-4 border-bottom border-secondary pb-2">MLM Configuration</h2>
                    <form action="update_config.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                        <div class="mb-3">
                            <label class="form-label small opacity-75">Entry Fee (Rs.)</label>
                            <input type="number" name="entry_fee" value="<?php echo e($entry_fee); ?>" class="glass-input">
                        </div>
                        <div class="mb-4">
                            <label class="form-label small opacity-75">Level Commission (Rs.)</label>
                            <input type="number" name="level_commission" value="<?php echo e($level_comm); ?>" class="glass-input">
                        </div>
                        <button type="submit" class="neon-button w-100">Update Settings</button>
                    </form>
                </section>
            </div>

            <div class="col-lg-8">
                <section class="glass-card p-4">
                    <h2 class="h5 mb-4 border-bottom border-secondary pb-2">Recent Transaction Logs</h2>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0">
                            <thead>
                                <tr class="text-uppercase small opacity-50">
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody class="align-middle">
                                <?php foreach($recent_transactions as $tx): ?>
                                <tr>
                                    <td><span class="badge bg-secondary">#<?php echo $tx['id']; ?></span></td>
                                    <td><?php echo e($tx['username']); ?></td>
                                    <td><span class="small text-info text-uppercase"><?php echo e($tx['type']); ?></span> <br><small class="opacity-50"><?php echo e($tx['bucket']); ?></small></td>
                                    <td class="fw-bold">Rs. <?php echo number_format($tx['amount'], 2); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($tx['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($recent_transactions)): ?>
                                <tr><td colspan="5" class="text-center py-5 opacity-50">No transactions found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>
