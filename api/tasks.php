<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// CORS Headers
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . ($origin !== '' ? $origin : '*'));
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$pdo = get_db_connection();

function respond(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// GET: List tasks for a student
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $student_no = trim($_GET['student_no'] ?? '');

    if ($student_no === '') {
        respond(400, ['success' => false, 'message' => 'student_no query parameter is required.']);
    }

    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE student_no = :sno ORDER BY (status = 'completed') ASC, deadline ASC, id DESC");
    $stmt->execute(['sno' => $student_no]);
    $tasks = $stmt->fetchAll();

    respond(200, [
        'success' => true,
        'count'   => count($tasks),
        'tasks'   => $tasks
    ]);
}

// POST: Create, Update, or Delete Task
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        respond(400, ['success' => false, 'message' => 'Invalid JSON body.']);
    }

    $action = $input['action'] ?? 'create';

    if ($action === 'create') {
        $student_no = trim($input['student_no'] ?? '');
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $deadline = !empty($input['deadline']) ? $input['deadline'] : null;
        $priority = in_array($input['priority'] ?? '', ['low', 'medium', 'high']) ? $input['priority'] : 'medium';

        if ($student_no === '' || $title === '') {
            respond(400, ['success' => false, 'message' => 'student_no and title are required.']);
        }

        $stmt = $pdo->prepare("INSERT INTO tasks (student_no, title, description, deadline, priority, status) VALUES (:sno, :title, :desc, :deadline, :priority, 'pending')");
        $stmt->execute([
            'sno'      => $student_no,
            'title'    => $title,
            'desc'     => $description,
            'deadline' => $deadline,
            'priority' => $priority
        ]);

        $newId = $pdo->lastInsertId();
        $tStmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id");
        $tStmt->execute(['id' => $newId]);

        respond(201, [
            'success' => true,
            'message' => 'Task created successfully',
            'task'    => $tStmt->fetch()
        ]);
    }

    if ($action === 'update_status') {
        $task_id = intval($input['task_id'] ?? 0);
        $status  = $input['status'] ?? 'completed';

        if ($task_id <= 0) {
            respond(400, ['success' => false, 'message' => 'Valid task_id is required.']);
        }

        $stmt = $pdo->prepare("UPDATE tasks SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $task_id]);

        $tStmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id");
        $tStmt->execute(['id' => $task_id]);

        respond(200, [
            'success' => true,
            'message' => 'Task status updated',
            'task'    => $tStmt->fetch()
        ]);
    }

    if ($action === 'delete') {
        $task_id = intval($input['task_id'] ?? 0);
        if ($task_id <= 0) {
            respond(400, ['success' => false, 'message' => 'Valid task_id is required.']);
        }
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
        $stmt->execute(['id' => $task_id]);

        respond(200, ['success' => true, 'message' => 'Task deleted']);
    }

    respond(400, ['success' => false, 'message' => 'Unknown action.']);
}

