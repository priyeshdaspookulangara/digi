<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }

    require_role('admin');

    $allowed_keys = ['entry_fee', 'level_commission', 'rebirth_milestone'];

    foreach ($_POST as $key => $value) {
        if (!in_array($key, $allowed_keys)) continue;

        // Compatible UPSERT pattern
        $stmt = $pdo->prepare("SELECT id FROM site_settings WHERE key_name = ?");
        $stmt->execute([$key]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE site_settings SET key_value = ? WHERE key_name = ?");
            $stmt->execute([$value, $key]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO site_settings (key_name, key_value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }
    }

    header("Location: index.php?success=1");
    exit;
}
?>
