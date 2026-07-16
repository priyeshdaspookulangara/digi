<?php
require_once 'includes/config.php';

// Fetch all localities
$localities = $pdo->query("SELECT name, latitude, longitude, district FROM localities")->fetchAll();

header('Content-Type: application/json');
echo json_encode($localities);
?>
