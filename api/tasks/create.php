<?php
// backend/api/tasks/create.php
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$user_id     = intval($input['user_id'] ?? 0);
$title       = trim($input['title'] ?? '');
$description = trim($input['description'] ?? '');
$deadline    = !empty($input['deadline']) ? $input['deadline'] : null;
$priority    = in_array($input['priority'] ?? '', ['low', 'medium', 'high']) ? $input['priority'] : 'medium';

if ($user_id <= 0 || empty($title)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID and Task Title are required.']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, deadline, priority, status) VALUES (:uid, :title, :desc, :deadline, :priority, 'pending')");
    $stmt->execute([
        'uid'      => $user_id,
        'title'    => $title,
        'desc'     => $description,
        'deadline' => $deadline,
        'priority' => $priority
    ]);

    $newId = $pdo->lastInsertId();
    $fetchStmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id");
    $fetchStmt->execute(['id' => $newId]);

    echo json_encode([
        'success' => true,
        'message' => 'Task created successfully',
        'task'    => $fetchStmt->fetch()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
