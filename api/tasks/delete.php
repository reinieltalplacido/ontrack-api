<?php
// backend/api/tasks/delete.php
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$task_id = intval($input['task_id'] ?? ($_GET['id'] ?? 0));

if ($task_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid task_id is required.']);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
    $stmt->execute(['id' => $task_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Task deleted successfully',
        'task_id' => $task_id
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

