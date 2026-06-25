<?php
// api/auth.php
require_once __DIR__ . '/api_helper.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Handle Login
    $input = json_decode(file_get_contents('php://input'), true);
    $identifier = $input['identifier'] ?? $input['email'] ?? ''; // Can be email, mobile, or customer_id
    $password = $input['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        send_error("Identifier and password are required.");
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR mobile = ? OR customer_id = ? OR username = ?");
    $stmt->execute([$identifier, $identifier, $identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Regerate session for security
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];

        // Get associated shop if owner
        $shop_id = null;
        if ($user['role'] === 'shop_owner') {
            $s_stmt = $pdo->prepare("SELECT id FROM shops WHERE user_id = ?");
            $s_stmt->execute([$user['id']]);
            $shop = $s_stmt->fetch();
            $shop_id = $shop['id'] ?? null;
            $_SESSION['shop_id'] = $shop_id;
        }

        send_json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'shop_id' => $shop_id
            ],
            'session_id' => session_id()
        ]);
    } else {
        send_error("Invalid credentials.", 401);
    }
} elseif ($method === 'GET') {
    // Check Status
    if (isset($_SESSION['user_id'])) {
        send_json([
            'logged_in' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role'],
                'shop_id' => $_SESSION['shop_id'] ?? null
            ]
        ]);
    } else {
        send_json(['logged_in' => false]);
    }
} elseif ($method === 'DELETE') {
    // Logout
    session_unset();
    session_destroy();
    send_json(['message' => 'Logged out successfully']);
} else {
    send_error("Method not allowed", 405);
}
?>
