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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register | NexGen Marketplace</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="glass-card" style="max-width: 400px; margin: 50px auto; padding: 40px;">
            <h2 class="neon-text">Join the Network</h2>
            <?php if ($error): ?><div class="alert error"><?php echo e($error); ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert success"><?php echo e($success); ?></div><?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="glass-input" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="glass-input" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="glass-input" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" class="glass-input">
                        <option value="member">Member</option>
                        <option value="shop_owner">Shop Owner</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Referrer Username (Optional)</label>
                    <input type="text" name="referrer" class="glass-input" value="<?php echo e($_GET['ref'] ?? ''); ?>">
                </div>
                <button type="submit" class="neon-button" style="width: 100%;">REGISTER</button>
            </form>
            <p style="margin-top:20px;">Already have an account? <a href="login.php" style="color:var(--neon-cyan);">Login here</a></p>
        </div>
    </div>
</body>
</html>
