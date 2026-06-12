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
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | NexGen Marketplace</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="glass-card" style="max-width: 400px; margin: 100px auto; padding: 40px;">
            <h2 class="neon-text" style="text-align: center;">Secure Login</h2>
            <?php if ($error): ?><div class="alert error"><?php echo e($error); ?></div><?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="glass-input" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="glass-input" required>
                </div>
                <button type="submit" class="neon-button" style="width: 100%;">ENTER THE VOID</button>
            </form>
            <p style="margin-top:20px;">Don't have an account? <a href="register.php" style="color:var(--neon-cyan);">Join the network</a></p>
        </div>
    </div>
</body>
</html>
