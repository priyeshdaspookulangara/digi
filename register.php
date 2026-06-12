<?php
require_once 'includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? 'member';
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
    $referrer_username = $_POST['referrer'] ?? '';

    // Find referrer ID
    $referrer_id = null;
    if ($referrer_username) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$referrer_username]);
        $referrer_id = $stmt->fetchColumn();
    }

    try {
        $pdo->beginTransaction();

        // Create User
        $stmt = $pdo->prepare("INSERT INTO users (username, email, role, password, referrer_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $role, $password, $referrer_id]);
        $user_id = $pdo->lastInsertId();

        // Add to MLM Hierarchy
        $parent_id = $referrer_id; // For simplicity, direct referrer is the parent
        $stmt = $pdo->prepare("INSERT INTO mlm_hierarchy (user_id, parent_id, level_in_tree) VALUES (?, ?, ?)");

        // Calculate tree level
        $tree_level = 1;
        if ($parent_id) {
            $stmt_p = $pdo->prepare("SELECT level_in_tree FROM mlm_hierarchy WHERE user_id = ?");
            $stmt_p->execute([$parent_id]);
            $tree_level = ($stmt_p->fetchColumn() ?: 0) + 1;
        }
        $stmt->execute([$user_id, $parent_id, $tree_level]);

        $pdo->commit();
        $success = "Registration successful! You can now login.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>
<?php
$pageTitle = "Register | NexGen Marketplace";
include 'includes/header.php';
?>

    <div class="container my-5">
        <div class="glass-card p-5 mx-auto" style="max-width: 500px;">
            <div class="text-center mb-4">
                <h2 class="neon-text h3">Join the Network</h2>
                <p class="small opacity-50">Create your account and start earning</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger bg-danger-subtle text-danger border-0 small mb-4">
                    <?php echo e($error); ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success bg-success-subtle text-success border-0 small mb-4">
                    <?php echo e($success); ?> <a href="login.php" class="alert-link">Login now</a>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small opacity-75">Username</label>
                        <input type="text" name="username" class="glass-input" placeholder="johndoe" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small opacity-75">Email Address</label>
                        <input type="email" name="email" class="glass-input" placeholder="john@example.com" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small opacity-75">Password</label>
                        <input type="password" name="password" class="glass-input" placeholder="••••••••" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small opacity-75">Account Type</label>
                        <select name="role" class="glass-input">
                            <option value="member">Member</option>
                            <option value="shop_owner">Shop Owner</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small opacity-75">Referrer (Optional)</label>
                        <input type="text" name="referrer" class="glass-input" placeholder="Username" value="<?php echo e($_GET['ref'] ?? ''); ?>">
                    </div>
                </div>

                <button type="submit" class="neon-button w-100 py-3 mt-4">CREATE ACCOUNT</button>
            </form>

            <p class="text-center small mt-4 mb-0">
                Already have an account? <a href="login.php" class="neon-cyan text-decoration-none fw-bold">Login here</a>
            </p>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
