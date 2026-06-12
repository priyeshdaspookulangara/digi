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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | NexGen Marketplace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
    <div class="container">
        <h1 class="neon-text">Central Admin Oversight</h1>

        <div class="stats-grid">
            <div class="glass-card stat-item">
                <h3>Total Users</h3>
                <p class="neon-text"><?php echo $total_users; ?></p>
            </div>
            <div class="glass-card stat-item">
                <h3>Total Revenue</h3>
                <p class="neon-text">Rs. <?php echo number_format($total_revenue, 2); ?></p>
            </div>
            <div class="glass-card stat-item">
                <h3>Commissions Paid</h3>
                <p class="neon-text">Rs. <?php echo number_format($total_commissions, 2); ?></p>
            </div>
        </div>

        <div class="admin-content">
            <section class="glass-card">
                <h2>MLM Configuration</h2>
                <form action="update_config.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom: 5px; opacity: 0.8;">Entry Fee (Rs.)</label>
                        <input type="number" name="entry_fee" value="<?php echo e($entry_fee); ?>" class="glass-input">
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom: 5px; opacity: 0.8;">Level Commission (Rs.)</label>
                        <input type="number" name="level_commission" value="<?php echo e($level_comm); ?>" class="glass-input">
                    </div>
                    <button type="submit" class="neon-button">Update Settings</button>
                </form>
            </section>

            <section class="glass-card">
                <h2>Transaction Logs</h2>
                <table class="glass-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_transactions as $tx): ?>
                        <tr>
                            <td>#<?php echo $tx['id']; ?></td>
                            <td><?php echo e($tx['username']); ?></td>
                            <td><?php echo e($tx['type']); ?> (<?php echo e($tx['bucket']); ?>)</td>
                            <td>Rs. <?php echo number_format($tx['amount'], 2); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($tx['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($recent_transactions)): ?>
                        <tr><td colspan="5">No transactions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</body>
</html>
