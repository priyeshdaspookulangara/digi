<?php
require_once 'includes/config.php';

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($type && $id) {
    $table = ($type === 'ad') ? 'ads' : 'banners';

    // Check if item exists and get target URL
    $stmt = $pdo->prepare("SELECT target_url FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if ($item) {
        // Increment clicks
        $stmt = $pdo->prepare("UPDATE $table SET clicks = clicks + 1 WHERE id = ?");
        $stmt->execute([$id]);

        // Redirect
        header("Location: " . $item['target_url']);
        exit;
    }
}

header("Location: index.php");
exit;
?>
