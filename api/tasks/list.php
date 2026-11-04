<?php
// backend/api/tasks/list.php
require_once __DIR__ . '/../../config/db.php';

$user_id = intval($_GET['user_id'] ?? 0);

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid user_id parameter.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = :uid ORDER BY (status = 'completed') ASC, deadline ASC, id DESC");
    $stmt->execute(['uid' => $user_id]);
    $tasks = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count'   => count($tasks),
        'tasks'   => $tasks
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
