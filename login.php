<?php
require_once 'includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Fetch shop_id if merchant
        if ($user['role'] === 'shop_owner') {
            $stmt_s = $pdo->prepare("SELECT id FROM shops WHERE owner_id = ?");
            $stmt_s->execute([$user['id']]);
            $_SESSION['shop_id'] = $stmt_s->fetchColumn();
        }

        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: admin/index.php");
        } elseif ($user['role'] === 'shop_owner') {
            header("Location: shop_portal.php");
        } elseif ($user['role'] === 'agent') {
            header("Location: agent_portal.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<?php
$pageTitle = "Login | NexGen Marketplace";
include 'includes/header.php';
?>

    <div class="container d-flex align-items-center justify-content-center" style="min-height: 80vh;">
        <div class="glass-card p-5" style="max-width: 450px; width: 100%;">
            <div class="text-center mb-4">
                <h2 class="neon-text h3">Secure Access</h2>
                <p class="small opacity-50">Enter your credentials to continue</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger bg-danger-subtle text-danger border-0 small mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small opacity-75">Username</label>
                    <div class="input-group">
                        <span class="input-group-text glass-input border-end-0"><i class="fas fa-user text-muted"></i></span>
                        <input type="text" name="username" class="glass-input border-start-0" placeholder="Username" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label small opacity-75">Password</label>
                    <div class="input-group">
                        <span class="input-group-text glass-input border-end-0"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" name="password" class="glass-input border-start-0" placeholder="••••••••" required>
                    </div>
                </div>
                <button type="submit" class="neon-button w-100 py-3 mb-4">AUTHENTICATE</button>
            </form>

            <p class="text-center small mb-0">
                Don't have an account? <a href="register.php" class="neon-cyan text-decoration-none fw-bold">Register Now</a>
            </p>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
