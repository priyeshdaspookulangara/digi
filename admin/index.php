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

$pageTitle = "Admin Dashboard | NexGen Marketplace";
require_once '../includes/admin_layout_header.php';
?>

<div class="page__heading d-flex align-items-center">
    <div class="flex">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
        </nav>
        <h1 class="m-0">Admin Dashboard</h1>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card p-4 text-center border-0 shadow-sm" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white;">
            <h3 class="h6 text-uppercase opacity-75">Total Users</h3>
            <p class="display-5 fw-bold mb-0"><?php echo $total_users; ?></p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 text-center border-0 shadow-sm" style="background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%); color: white;">
            <h3 class="h6 text-uppercase opacity-75">Total Revenue</h3>
            <p class="display-6 fw-bold mb-0">₹<?php echo number_format($total_revenue, 2); ?></p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 text-center border-0 shadow-sm" style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); color: white;">
            <h3 class="h6 text-uppercase opacity-75">Commissions Paid</h3>
            <p class="display-6 fw-bold mb-0">₹<?php echo number_format($total_commissions, 2); ?></p>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="card-title m-0 fw-bold">MLM Configuration</h5>
            </div>
            <div class="card-body">
                <form action="update_config.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Entry Fee (₹)</label>
                        <input type="number" name="entry_fee" value="<?php echo e($entry_fee); ?>" class="form-control" style="border-radius: 8px;">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Level Commission (₹)</label>
                        <input type="number" name="level_commission" value="<?php echo e($level_comm); ?>" class="form-control" style="border-radius: 8px;">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="border-radius: 8px; background-color: #6366f1; border: none;">Update Settings</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title m-0 fw-bold">Recent Transaction Logs</h5>
                <a href="#" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr class="text-uppercase small text-muted">
                            <th class="ps-4">ID</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        <?php foreach($recent_transactions as $tx): ?>
                        <tr>
                            <td class="ps-4"><span class="badge bg-light text-dark border">#<?php echo $tx['id']; ?></span></td>
                            <td class="fw-bold"><?php echo e($tx['username']); ?></td>
                            <td>
                                <span class="text-primary small fw-bold text-uppercase"><?php echo e($tx['type']); ?></span>
                                <?php if($tx['bucket']): ?><br><small class="text-muted"><?php echo e($tx['bucket']); ?></small><?php endif; ?>
                            </td>
                            <td class="fw-bold text-success">₹<?php echo number_format($tx['amount'], 2); ?></td>
                            <td class="text-muted small"><?php echo date('M d, Y', strtotime($tx['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($recent_transactions)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No transactions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_layout_footer.php'; ?>
