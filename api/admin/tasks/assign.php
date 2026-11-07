<?php
declare(strict_types=1);

require_once __DIR__ . '/../../common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$body = read_json_body();
$title = trim((string)($body['title'] ?? ''));
$description = trim((string)($body['description'] ?? ''));
$priority = strtolower(trim((string)($body['priority'] ?? 'medium')));
$deadline = trim((string)($body['deadline'] ?? ''));
$targetType = trim((string)($body['target_type'] ?? 'student')); // 'student' | 'section'
$userId = filter_var($body['user_id'] ?? null, FILTER_VALIDATE_INT);
$section = trim((string)($body['section'] ?? ''));
$professorId = filter_var($body['professor_id'] ?? null, FILTER_VALIDATE_INT);

if ($title === '') {
    respond(400, ['success' => false, 'message' => 'Task title is required.']);
}

$validPriorities = ['low', 'medium', 'high'];
if (!in_array($priority, $validPriorities, true)) {
    $priority = 'medium';
}

if ($deadline === '') {
    $deadline = date('Y-m-d', strtotime('+7 days'));
}

try {
    $pdo = get_db_connection();

    $assignedCount = 0;

    if ($targetType === 'section' && $section !== '') {
        // Assign to all students in this section
        $sStmt = $pdo->prepare('SELECT id, student_no FROM students WHERE course_section = :sec');
        $sStmt->execute(['sec' => $section]);
        $targetStudents = $sStmt->fetchAll();

        if (empty($targetStudents)) {
            respond(404, ['success' => false, 'message' => "No students found in section {$section}."]);
        }

        $insStmt = $pdo->prepare('INSERT INTO tasks (user_id, student_no, title, description, priority, deadline, status, created_at) VALUES (:uid, :sno, :title, :desc, :prio, :deadline, "pending", NOW())');
        foreach ($targetStudents as $ts) {
            $insStmt->execute([
                'uid'      => (int)$ts['id'],
                'sno'      => $ts['student_no'],
                'title'    => $title,
                'desc'     => $description ?: null,
                'prio'     => $priority,
                'deadline' => $deadline,
            ]);
            $assignedCount++;
        }

        respond(201, [
            'success'        => true,
            'message'        => "Task successfully assigned to {$assignedCount} student(s) in {$section}.",
            'assigned_count' => $assignedCount,
        ]);
    } else {
        // Assign to single student
        if (!$userId) {
            respond(400, ['success' => false, 'message' => 'Target student user_id is required.']);
        }

        $sStmt = $pdo->prepare('SELECT id, student_no FROM students WHERE id = :id LIMIT 1');
        $sStmt->execute(['id' => $userId]);
        $st = $sStmt->fetch();

        if (!$st) {
            respond(404, ['success' => false, 'message' => 'Target student not found.']);
        }

        $insStmt = $pdo->prepare('INSERT INTO tasks (user_id, student_no, title, description, priority, deadline, status, created_at) VALUES (:uid, :sno, :title, :desc, :prio, :deadline, "pending", NOW())');
        $insStmt->execute([
            'uid'      => (int)$st['id'],
            'sno'      => $st['student_no'],
            'title'    => $title,
            'desc'     => $description ?: null,
            'prio'     => $priority,
            'deadline' => $deadline,
        ]);

        $taskId = (int)$pdo->lastInsertId();

        respond(201, [
            'success' => true,
            'message' => 'Task assigned to student successfully.',
            'task_id' => $taskId,
        ]);
    }
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

