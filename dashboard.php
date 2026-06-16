<?php
require_once 'includes/config.php';
require_login();

$user_id = $_SESSION['user_id'];

// Minimalist Tree Fetcher (Downline only)
function getDownline($pdo, $parent_id, $depth = 1) {
    if ($depth > 3) return []; // Limit depth for visualization
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.level FROM users u JOIN mlm_hierarchy m ON u.id = m.user_id WHERE m.parent_id = ?");
    $stmt->execute([$parent_id]);
    $members = $stmt->fetchAll();

    foreach ($members as &$m) {
        $m['children'] = getDownline($pdo, $m['id'], $depth + 1);
    }
    return $members;
}

$tree = getDownline($pdo, $user_id);

$pageTitle = "MLM Dashboard | My Network";
include 'includes/header.php';
?>

<div class="container my-5">
    <h1 class="neon-text mb-4">My Referral Network</h1>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="glass-card p-4">
                <h3 class="h5 mb-4 border-bottom border-secondary pb-2">Downline Visualizer</h3>
                <div class="tree-container py-4">
                    <div class="tree-node parent mb-5 text-center">
                        <div class="glass-card d-inline-block p-3 border-info">
                            <i class="fas fa-user-crown fa-2x mb-2 neon-cyan"></i>
                            <h5 class="m-0"><?php echo e($_SESSION['username']); ?></h5>
                            <small class="opacity-50">Root Node</small>
                        </div>
                    </div>

                    <div class="row g-4 justify-content-center">
                        <?php foreach($tree as $child): ?>
                        <div class="col-md-4 text-center">
                            <div class="glass-card p-3 border-secondary bg-white-10">
                                <i class="fas fa-user-plus mb-2 neon-purple"></i>
                                <h6 class="m-0"><?php echo e($child['username']); ?></h6>
                                <small class="opacity-50">Level <?php echo $child['level']; ?></small>

                                <?php if(!empty($child['children'])): ?>
                                <div class="mt-3 pt-2 border-top border-secondary border-opacity-25">
                                    <small class="d-block mb-1">Direct Referrals: <?php echo count($child['children']); ?></small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <?php if(empty($tree)): ?>
                        <div class="text-center py-5 opacity-25">
                            <p>No active downline members found yet.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="glass-card p-4">
                <h3 class="h5 mb-4 border-bottom border-secondary pb-2">Network Stats</h3>
                <div class="d-flex justify-content-between mb-3">
                    <span class="opacity-75">Direct Referrals</span>
                    <span class="neon-cyan fw-bold"><?php echo count($tree); ?></span>
                </div>
                <!-- Mock stats for UI -->
                <div class="d-flex justify-content-between mb-3">
                    <span class="opacity-75">Total Downline</span>
                    <span class="neon-purple fw-bold"><?php echo count($tree) * 2; ?></span>
                </div>
                <hr class="border-secondary opacity-25">
                <button class="neon-button w-100" onclick="alert('Referral link copied!')">Copy Invite Link</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
