<?php
require_once 'includes/config.php';

$action = $_GET['action'] ?? '';
$shop_id = (int)($_GET['shop_id'] ?? 0);
$date = $_GET['date'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'get_enquiry_dates':
        // Return unique dates that have enquiries for marker dots
        $stmt = $pdo->prepare("SELECT DISTINCT DATE(created_at) as date FROM enquiries WHERE shop_id = ?");
        $stmt->execute([$shop_id]);
        $dates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $events = [];
        foreach ($dates as $d) {
            $events[] = [
                'start' => $d['date'],
                'display' => 'background',
                'color' => '#6366f1'
            ];
        }
        echo json_encode($events);
        break;

    case 'get_enquiries_for_date':
        // Return enquiries for a specific date
        $stmt = $pdo->prepare("
            SELECT e.*, p.name as product_name
            FROM enquiries e
            LEFT JOIN products p ON e.product_id = p.id
            WHERE e.shop_id = ? AND DATE(e.created_at) = ?
        ");
        $stmt->execute([$shop_id, $date]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
