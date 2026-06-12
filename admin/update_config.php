<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }

    require_role('admin');

    foreach ($_POST as $key => $value) {
        if ($key === 'csrf_token') continue;

        // Handle MySQL and SQLite UPSERT
        $stmt = $pdo->prepare("INSERT INTO site_settings (key_name, key_value) VALUES (?, ?) ON CONFLICT(key_name) DO UPDATE SET key_value = excluded.key_value");
        $stmt->execute([$key, $value]);
    }

    header("Location: index.php?success=1");
    exit;
}
?>
