<?php
declare(strict_types=1);

require_once __DIR__ . '/../../common.php';

$professorId = filter_input(INPUT_GET, 'professor_id', FILTER_VALIDATE_INT);
$section = trim((string)($_GET['section'] ?? ''));
$search = trim((string)($_GET['search'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? 'all')); // all, on_duty, off_duty

try {
    $pdo = get_db_connection();

    $query = 'SELECT s.*, p.full_name as adviser_full_name FROM students s LEFT JOIN professors p ON s.professor_id = p.id WHERE 1=1';
    $params = [];

    if ($professorId) {
        $query .= ' AND s.professor_id = :prof_id';
        $params['prof_id'] = $professorId;
    }

    if ($section !== '') {
        $query .= ' AND s.course_section = :sec';
        $params['sec'] = $section;
    }

    if ($search !== '') {
        $query .= ' AND (s.full_name LIKE :search1 OR s.student_no LIKE :search2 OR s.company_name LIKE :search3)';
        $searchParam = "%{$search}%";
        $params['search1'] = $searchParam;
        $params['search2'] = $searchParam;
        $params['search3'] = $searchParam;
    }

    $query .= ' ORDER BY s.full_name ASC';
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll();

    $todayStr = date('Y-m-d');
    $results = [];

    // Prepare statements ONCE before the loop for maximum performance
    $hStmt = $pdo->prepare("SELECT SUM(total_hours) as total_hrs FROM attendance_logs WHERE user_id = :uid AND status = 'completed'");
    $dutyStmt = $pdo->prepare("SELECT id, time_in, location_name FROM attendance_logs WHERE user_id = :uid AND log_date = :today AND status = 'ongoing' LIMIT 1");
    $tStmt = $pdo->prepare("SELECT COUNT(*) as pending_cnt FROM tasks WHERE user_id = :uid AND status != 'completed'");

    foreach ($students as $s) {
        $studentId = (int)$s['id'];
        $studentNo = $s['student_no'];
        $requiredHours = floatval($s['required_hours'] ?? 486);

        // 1. Compute accumulated hours using indexed user_id lookup
        $hStmt->execute(['uid' => $studentId]);
        $hRow = $hStmt->fetch();
        $accumulated = round(floatval($hRow['total_hrs'] ?? 0), 2);

        // 2. Check if currently on duty today using index
        $dutyStmt->execute(['uid' => $studentId, 'today' => $todayStr]);
        $ongoingLog = $dutyStmt->fetch();
        $isOnDuty = !empty($ongoingLog);

        // 3. Check pending tasks using index
        $tStmt->execute(['uid' => $studentId]);
        $tRow = $tStmt->fetch();
        $pendingTasksCount = (int)($tRow['pending_cnt'] ?? 0);

        $percent = $requiredHours > 0 ? round(($accumulated / $requiredHours) * 100, 1) : 0;
        if ($percent > 100) $percent = 100;
        $remaining = max(0, round($requiredHours - $accumulated, 2));

        // Filter by duty status if requested
        if ($statusFilter === 'on_duty' && !$isOnDuty) continue;
        if ($statusFilter === 'off_duty' && $isOnDuty) continue;

        $results[] = [
            'id'                  => $studentId,
            'student_no'          => $studentNo,
            'full_name'           => $s['full_name'],
            'email'               => $s['email'] ?? ($studentNo . '@student.neust.edu.ph'),
            'course'              => $s['course'] ?? 'BSIT',
            'course_section'      => $s['course_section'] ?? 'BSIT 4-A',
            'school_campus'       => $s['school_campus'] ?? 'NEUST - Sumacab Campus',
            'company_name'        => $s['company_name'] ?? 'Assigned Agency',
            'required_hours'      => (int)$requiredHours,
            'accumulated_hours'   => $accumulated,
            'remaining_hours'     => $remaining,
            'completion_percent'  => $percent,
            'target_date'         => $s['target_date'] ?? '2026-12-15',
            'is_on_duty'          => $isOnDuty,
            'current_shift'       => $ongoingLog ?: null,
            'pending_tasks_count' => $pendingTasksCount,
            'professor_id'        => (int)($s['professor_id'] ?? 0),
            'adviser_name'        => $s['adviser_full_name'] ?? ($s['adviser_name'] ?? 'OJT Coordinator'),
        ];
    }

    respond(200, [
        'success'  => true,
        'count'    => count($results),
        'students' => $results,
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
