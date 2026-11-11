<?php
declare(strict_types=1);

require_once __DIR__ . '/../common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$input = read_json_body();
$userId = filter_var($input['user_id'] ?? null, FILTER_VALIDATE_INT);
$studentNo = trim((string)($input['student_no'] ?? ''));
$title = trim((string)($input['title'] ?? ''));
$description = trim((string)($input['description'] ?? ''));
$deadline = !empty($input['deadline']) ? $input['deadline'] : null;
$priority = in_array($input['priority'] ?? '', ['low', 'medium', 'high']) ? $input['priority'] : 'medium';

if (!$userId && $studentNo === '') {
    respond(400, ['success' => false, 'message' => 'user_id or student_no is required.']);
}
if ($title === '') {
    respond(422, ['success' => false, 'message' => 'Task title is required.']);
}

try {
    $pdo = get_db_connection();

    // Find student
    if ($userId) {
        $stmt = $pdo->prepare('SELECT id, student_no FROM students WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
    } else {
        $stmt = $pdo->prepare('SELECT id, student_no FROM students WHERE student_no = :sno LIMIT 1');
        $stmt->execute(['sno' => $studentNo]);
    }
    $student = $stmt->fetch();
    $sId = $student ? (int)$student['id'] : ($userId ?? 1);
    $sNo = $student ? $student['student_no'] : ($studentNo !== '' ? $studentNo : '2026-0001');

    $proofUrl = null;
    $proofFile = null;

    if (!empty($input['proof_image'])) {
        $saved = save_base64_upload(
            (string)$input['proof_image'],
            'tasks',
            'task_proof_new_',
            (string)($input['proof_file_name'] ?? '')
        );
        if ($saved !== null) {
            $proofUrl = $saved['url'];
            $proofFile = $saved['name'];
        }
    }

    $ins = $pdo->prepare(
        "INSERT INTO tasks (user_id, student_no, title, description, deadline, priority, status, proof_url, proof_file)
         VALUES (:uid, :sno, :title, :desc, :deadline, :priority, 'pending', :proof_url, :proof_file)"
    );
    $ins->execute([
        'uid'        => $sId,
        'sno'        => $sNo,
        'title'      => $title,
        'desc'       => $description !== '' ? $description : null,
        'deadline'   => $deadline,
        'priority'   => $priority,
        'proof_url'  => $proofUrl,
        'proof_file' => $proofFile,
    ]);

    $newId = (int)$pdo->lastInsertId();
    $fetchTask = $pdo->prepare('SELECT * FROM tasks WHERE id = :id');
    $fetchTask->execute(['id' => $newId]);
    $created = $fetchTask->fetch();
    $created['id'] = (int)$created['id'];
    $created['user_id'] = (int)($created['user_id'] ?? $sId);

    respond(201, [
        'success' => true,
        'message' => 'Task created successfully.',
        'task'    => $created,
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

