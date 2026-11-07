<?php
declare(strict_types=1);

require_once __DIR__ . '/../../common.php';

$studentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$studentNo = trim((string)($_GET['student_no'] ?? ''));

if (!$studentId && $studentNo === '') {
    respond(400, ['success' => false, 'message' => 'Student ID (id) or student_no query parameter is required.']);
}

try {
    $pdo = get_db_connection();

    // 1. Fetch student info and assigned adviser
    if ($studentId) {
        $stmt = $pdo->prepare('SELECT s.*, p.full_name as adviser_full_name, p.email as adviser_full_email 
                               FROM students s 
                               LEFT JOIN professors p ON s.professor_id = p.id 
                               WHERE s.id = :id 
                               LIMIT 1');
        $stmt->execute(['id' => $studentId]);
    } else {
        $stmt = $pdo->prepare('SELECT s.*, p.full_name as adviser_full_name, p.email as adviser_full_email 
                               FROM students s 
                               LEFT JOIN professors p ON s.professor_id = p.id 
                               WHERE s.student_no = :sno 
                               LIMIT 1');
        $stmt->execute(['sno' => $studentNo]);
    }

    $student = $stmt->fetch();
    if (!$student) {
        respond(404, ['success' => false, 'message' => 'Student not found.']);
    }

    $sId = (int)$student['id'];
    $sNo = (string)$student['student_no'];
    $requiredHours = floatval($student['required_hours'] ?? 486);

    // 2. Fetch attendance logs using fast index seek (avoids unindexed OR scan)
    if ($sId > 0) {
        $lStmt = $pdo->prepare('SELECT * FROM attendance_logs WHERE user_id = :uid ORDER BY log_date DESC, time_in DESC');
        $lStmt->execute(['uid' => $sId]);
    } else {
        $lStmt = $pdo->prepare('SELECT * FROM attendance_logs WHERE student_no = :sno ORDER BY log_date DESC, time_in DESC');
        $lStmt->execute(['sno' => $sNo]);
    }
    $logs = $lStmt->fetchAll();

    $accumulated = 0.0;
    $distinctDays = [];
    foreach ($logs as &$log) {
        $log['id'] = (int)$log['id'];
        $log['user_id'] = (int)($log['user_id'] ?? $sId);
        $log['total_hours'] = floatval($log['total_hours'] ?? 0);
        if ($log['status'] === 'completed') {
            $accumulated += $log['total_hours'];
        }
        $distinctDays[$log['log_date']] = true;
    }
    unset($log);

    $accumulated = round($accumulated, 2);
    $remaining = max(0, round($requiredHours - $accumulated, 2));
    $percent = $requiredHours > 0 ? round(($accumulated / $requiredHours) * 100, 1) : 0;
    if ($percent > 100) $percent = 100;

    // 3. Fetch tasks using fast index seek
    if ($sId > 0) {
        $tStmt = $pdo->prepare('SELECT * FROM tasks WHERE user_id = :uid ORDER BY id DESC');
        $tStmt->execute(['uid' => $sId]);
    } else {
        $tStmt = $pdo->prepare('SELECT * FROM tasks WHERE student_no = :sno ORDER BY id DESC');
        $tStmt->execute(['sno' => $sNo]);
    }
    $tasks = $tStmt->fetchAll();

    respond(200, [
        'success' => true,
        'student' => [
            'id'                 => $sId,
            'student_no'         => $sNo,
            'full_name'          => $student['full_name'],
            'email'              => $student['email'] ?? ($sNo . '@student.neust.edu.ph'),
            'course'             => $student['course'] ?? 'BSIT',
            'course_section'     => $student['course_section'] ?? 'BSIT 4-A',
            'year_level'         => (int)($student['year_level'] ?? 4),
            'school_campus'      => $student['school_campus'] ?? 'NEUST - Sumacab Campus',
            'company_name'       => $student['company_name'] ?? 'Assigned Agency',
            'required_hours'     => (int)$requiredHours,
            'accumulated_hours'  => $accumulated,
            'remaining_hours'    => $remaining,
            'completion_percent' => $percent,
            'days_logged'        => count($distinctDays),
            'target_date'        => $student['target_date'] ?? '2026-12-15',
            'adviser_name'       => $student['adviser_full_name'] ?? ($student['adviser_name'] ?? 'OJT Coordinator'),
            'adviser_email'      => $student['adviser_full_email'] ?? ($student['adviser_email'] ?? 'adviser@neust.edu.ph'),
        ],
        'logs'  => $logs,
        'tasks' => $tasks,
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
