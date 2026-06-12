<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }

    require_role('admin');

    // Create settings table if not exists (lazy migration)
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (key TEXT PRIMARY KEY, value TEXT)");

    foreach ($_POST as $key => $value) {
        if ($key === 'csrf_token') continue;

        $stmt = $pdo->prepare("INSERT OR REPLACE INTO site_settings (key, value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }

    header("Location: index.php?success=1");
    exit;
}
?>
