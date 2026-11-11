<?php
declare(strict_types=1);

require_once __DIR__ . '/../common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$body = read_json_body();
$userId = filter_var($body['user_id'] ?? ($body['id'] ?? null), FILTER_VALIDATE_INT);
$role = strtolower(trim((string)($body['role'] ?? 'student')));
$currentPassword = (string)($body['current_password'] ?? '');
$newPassword = (string)($body['new_password'] ?? '');
$confirmPassword = (string)($body['confirm_password'] ?? '');

if (!$userId) {
    respond(400, ['success' => false, 'message' => 'User ID is required.']);
}

if ($newPassword === '') {
    respond(400, ['success' => false, 'message' => 'New password cannot be empty.']);
}

if (strlen($newPassword) < 6) {
    respond(400, ['success' => false, 'message' => 'New password must be at least 6 characters.']);
}

if ($confirmPassword !== '' && $newPassword !== $confirmPassword) {
    respond(400, ['success' => false, 'message' => 'New password and confirmation do not match.']);
}

try {
    $pdo = get_db_connection();

    if ($role === 'professor' || $role === 'admin' || $role === 'coordinator') {
        $stmt = $pdo->prepare('SELECT id, password, full_name, email FROM professors WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            respond(404, ['success' => false, 'message' => 'Coordinator account not found.']);
        }

        // Verify current password if provided
        if ($currentPassword !== '') {
            $matches = password_verify($currentPassword, (string)$user['password']) || ($currentPassword === $user['password']);
            if (!$matches) {
                respond(401, ['success' => false, 'message' => 'Incorrect current password. Please try again.']);
            }
        }

        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $up = $pdo->prepare('UPDATE professors SET password = :pwd WHERE id = :id');
        $up->execute(['pwd' => $hashed, 'id' => $userId]);

        respond(200, [
            'success' => true,
            'message' => 'Coordinator password updated successfully.',
        ]);
    } else {
        // Student
        $stmt = $pdo->prepare('SELECT id, password, full_name, email FROM students WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            respond(404, ['success' => false, 'message' => 'Student account not found.']);
        }

        // Verify current password if provided
        if ($currentPassword !== '') {
            $matches = password_verify($currentPassword, (string)$user['password']) || ($currentPassword === $user['password']);
            if (!$matches) {
                respond(401, ['success' => false, 'message' => 'Incorrect current password. Please try again.']);
            }
        }

        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $up = $pdo->prepare('UPDATE students SET password = :pwd WHERE id = :id');
        $up->execute(['pwd' => $hashed, 'id' => $userId]);

        respond(200, [
            'success' => true,
            'message' => 'Student password updated successfully.',
        ]);
    }
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
