<?php
// backend/api/auth/login.php
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$identifier = trim($input['email'] ?? $input['student_id'] ?? $input['identifier'] ?? '');
$password = trim($input['password'] ?? '');

if (empty($identifier) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Student ID or Email and password are required.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email OR student_id = :sid LIMIT 1");
    $stmt->execute(['email' => $identifier, 'sid' => $identifier]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        exit();
    }

    // Verify Password (supports bcrypt hash or plain text fallback for demo setup)
    $passwordValid = password_verify($password, $user['password']) || ($password === $user['password']);

    if (!$passwordValid) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        exit();
    }

    // Compute total accumulated hours
    $hoursStmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours), 0) AS total FROM attendance_logs WHERE user_id = :uid AND status = 'completed'");
    $hoursStmt->execute(['uid' => $user['id']]);
    $hoursRow = $hoursStmt->fetch();
    $rendered_hours = floatval($hoursRow['total'] ?? 0);

    // Remove sensitive password hash from response
    unset($user['password']);
    $user['rendered_hours'] = $rendered_hours;

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => $user,
        'token' => base64_encode($user['id'] . ':' . time())
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
