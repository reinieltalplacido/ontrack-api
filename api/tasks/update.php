<?php
// backend/api/tasks/update.php
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$task_id = intval($input['task_id'] ?? 0);
$status  = $input['status'] ?? null;

if ($task_id <= 0 || !in_array($status, ['pending', 'in_progress', 'completed'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid task_id and status are required.']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE tasks SET status = :status WHERE id = :id");
    $stmt->execute(['status' => $status, 'id' => $task_id]);

    $fetchStmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id");
    $fetchStmt->execute(['id' => $task_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Task updated successfully',
        'task'    => $fetchStmt->fetch()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
