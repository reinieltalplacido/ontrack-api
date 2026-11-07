<?php
declare(strict_types=1);

require_once __DIR__ . '/../../common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Method not allowed. Only POST is accepted.']);
}

$body = read_json_body();
$taskId = filter_var($body['task_id'] ?? null, FILTER_VALIDATE_INT);
$verifiedBy = trim((string)($body['verified_by'] ?? 'Coordinator'));

if (!$taskId) {
    respond(400, ['success' => false, 'message' => 'task_id is required.']);
}

try {
    $pdo = get_db_connection();

    $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed', verified_at = NOW(), verified_by = :vby WHERE id = :id");
    $stmt->execute([
        'vby' => $verifiedBy,
        'id'  => $taskId,
    ]);

    respond(200, [
        'success' => true,
        'message' => 'Task verified and approved successfully.',
        'task_id' => $taskId,
        'verified_at' => date('Y-m-d H:i:s'),
        'verified_by' => $verifiedBy,
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

