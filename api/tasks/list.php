<?php
declare(strict_types=1);

require_once __DIR__ . '/../common.php';

$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$studentNo = trim((string)($_GET['student_no'] ?? ''));

if (!$userId && $studentNo === '') {
    respond(400, ['success' => false, 'message' => 'user_id or student_no query parameter is required.']);
}

try {
    $pdo = get_db_connection();

    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = :uid OR student_no = :sno ORDER BY (status = 'completed') ASC, deadline ASC, id DESC");
    $stmt->execute(['uid' => $userId ?? 0, 'sno' => $studentNo]);
    $tasks = $stmt->fetchAll();

    foreach ($tasks as &$task) {
        $task['id'] = (int)$task['id'];
        $task['user_id'] = (int)($task['user_id'] ?? $userId);
    }
    unset($task);

    respond(200, [
        'success' => true,
        'count'   => count($tasks),
        'tasks'   => $tasks,
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

