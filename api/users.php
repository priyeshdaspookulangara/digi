<?php
// api/users.php
require_once __DIR__ . '/api_helper.php';

api_require_role('admin');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT id, username, email, mobile, customer_id, role, level, created_at FROM users WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $user = $stmt->fetch();
        if ($user) send_json($user);
        else send_error("User not found", 404);
    } else {
        $role_filter = $_GET['role'] ?? null;
        if ($role_filter) {
            $stmt = $pdo->prepare("SELECT id, username, email, mobile, customer_id, role, level, created_at FROM users WHERE role = ? ORDER BY id DESC");
            $stmt->execute([$role_filter]);
        } else {
            $stmt = $pdo->query("SELECT id, username, email, mobile, customer_id, role, level, created_at FROM users ORDER BY id DESC");
        }
        send_json($stmt->fetchAll());
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $username = $input['username'] ?? '';
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $mobile = $input['mobile'] ?? '';
    $customer_id = $input['customer_id'] ?? null;
    $role = $input['role'] ?? 'member';

    if (empty($username) || empty($password)) send_error("Username and password are required.");

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, mobile, customer_id, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $username,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $mobile,
            $customer_id,
            $role
        ]);
        send_json(['message' => 'User created successfully', 'id' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        send_error("Failed to create user: " . $e->getMessage());
    }
} else {
    send_error("Method not allowed", 405);
}
?>
