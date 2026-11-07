<?php
declare(strict_types=1);

require_once __DIR__ . '/../common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$input = read_json_body();
$taskId = filter_var($input['task_id'] ?? ($input['id'] ?? null), FILTER_VALIDATE_INT);

if (!$taskId) {
    respond(400, ['success' => false, 'message' => 'Valid task_id is required.']);
}

try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = :id');
    $stmt->execute(['id' => $taskId]);

    respond(200, [
        'success' => true,
        'message' => 'Task deleted successfully.',
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

