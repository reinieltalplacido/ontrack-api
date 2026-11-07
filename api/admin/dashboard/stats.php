<?php
declare(strict_types=1);

require_once __DIR__ . '/../../common.php';

$professorId = filter_input(INPUT_GET, 'professor_id', FILTER_VALIDATE_INT);
$section = trim((string)($_GET['section'] ?? ''));

try {
    $pdo = get_db_connection();

    // 1. Total students
    $studentQuery = 'SELECT id, student_no, full_name, required_hours FROM students WHERE 1=1';
    $params = [];
    if ($professorId) {
        $studentQuery .= ' AND professor_id = :prof_id';
        $params['prof_id'] = $professorId;
    }
    if ($section !== '') {
        $studentQuery .= ' AND course_section = :sec';
        $params['sec'] = $section;
    }

    $stmt = $pdo->prepare($studentQuery);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    $totalStudents = count($students);

    // 2. Attendance Stats Today
    $todayStr = date('Y-m-d');
    $todayStmt = $pdo->prepare('SELECT a.*, s.full_name as student_name, s.student_no FROM attendance_logs a JOIN students s ON a.student_no = s.student_no WHERE a.log_date = :today ORDER BY a.time_in DESC');
    $todayStmt->execute(['today' => $todayStr]);
    $todayLogs = $todayStmt->fetchAll();

    $activeOnDuty = 0;
    $completedDutyToday = 0;
    foreach ($todayLogs as $tl) {
        if ($tl['status'] === 'ongoing') {
            $activeOnDuty++;
        } elseif ($tl['status'] === 'completed') {
            $completedDutyToday++;
        }
    }

    // 3. Total Rendered Hours across system
    $hrsStmt = $pdo->query("SELECT SUM(total_hours) as total_rendered FROM attendance_logs WHERE status = 'completed'");
    $totalRenderedRow = $hrsStmt->fetch();
    $totalHoursRendered = round(floatval($totalRenderedRow['total_rendered'] ?? 0), 2);

    // 4. Overall Progress Calculation
    $totalRequiredHours = 0;
    foreach ($students as $st) {
        $totalRequiredHours += (int)($st['required_hours'] ?? 486);
    }
    $overallPercent = $totalRequiredHours > 0 ? round(($totalHoursRendered / $totalRequiredHours) * 100, 1) : 0;
    if ($overallPercent > 100) $overallPercent = 100;

    // 5. Tasks Metrics
    $taskStmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM tasks GROUP BY status");
    $taskCounts = $taskStmt->fetchAll();
    $totalTasks = 0;
    $pendingTasks = 0;
    $completedTasks = 0;
    foreach ($taskCounts as $tc) {
        $cnt = (int)$tc['cnt'];
        $totalTasks += $cnt;
        if ($tc['status'] === 'completed') {
            $completedTasks += $cnt;
        } else {
            $pendingTasks += $cnt;
        }
    }

    // 6. Recent attendance activities (latest 8)
    $recentStmt = $pdo->query("SELECT a.id, a.student_no, a.log_date, a.time_in, a.time_out, a.total_hours, a.location_name, a.photo_url, a.activity_notes, a.status, s.full_name as student_name, s.course_section, s.company_name FROM attendance_logs a LEFT JOIN students s ON a.student_no = s.student_no ORDER BY a.id DESC LIMIT 8");
    $recentActivities = $recentStmt->fetchAll();

    respond(200, [
        'success' => true,
        'stats' => [
            'total_students'       => $totalStudents,
            'active_on_duty'       => $activeOnDuty,
            'completed_duty_today' => $completedDutyToday,
            'total_hours_rendered' => $totalHoursRendered,
            'total_required_hours' => $totalRequiredHours,
            'overall_percent'      => $overallPercent,
            'total_tasks'          => $totalTasks,
            'pending_tasks'        => $pendingTasks,
            'completed_tasks'      => $completedTasks,
            'today_date'           => $todayStr,
        ],
        'recent_activities' => $recentActivities,
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

