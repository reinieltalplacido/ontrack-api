<?php
declare(strict_types=1);

require_once __DIR__ . '/../../common.php';

$studentId = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);
$status = trim((string)($_GET['status'] ?? ''));
$section = trim((string)($_GET['section'] ?? ''));

try {
    $pdo = get_db_connection();

    $query = "SELECT t.*, s.full_name as student_name, s.student_no, s.course_section 
              FROM tasks t 
              LEFT JOIN students s ON (t.user_id = s.id OR t.student_no = s.student_no)
              WHERE 1=1";
    $params = [];

    if ($studentId) {
        $query .= " AND (t.user_id = :sid OR s.id = :sid)";
        $params['sid'] = $studentId;
    }

    if ($status !== '' && $status !== 'all') {
        $query .= " AND t.status = :status";
        $params['status'] = $status;
    }

    if ($section !== '' && $section !== 'all') {
        $query .= " AND s.course_section = :section";
        $params['section'] = $section;
    }

    $query .= " ORDER BY (t.status = 'completed') ASC, (t.proof_file IS NOT NULL AND t.proof_file != '') DESC, t.deadline ASC, t.id DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();

    foreach ($tasks as &$t) {
        $t['id'] = (int)$t['id'];
        $t['user_id'] = (int)($t['user_id'] ?? 0);
        $t['student_name'] = $t['student_name'] ?? 'Assigned Student';
    }
    unset($t);

    respond(200, [
        'success' => true,
        'count'   => count($tasks),
        'tasks'   => $tasks,
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

