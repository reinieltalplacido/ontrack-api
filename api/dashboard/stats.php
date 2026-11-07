<?php
declare(strict_types=1);

require_once __DIR__ . '/../common.php';

$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$studentNo = trim((string)($_GET['student_no'] ?? ''));

if (!$userId && $studentNo === '') {
    respond(400, ['success' => false, 'message' => 'user_id or student_no query parameter is required.']);
}

try {
    $pdo = get_db_connection();

    if ($userId) {
        $sStmt = $pdo->prepare('SELECT * FROM students WHERE id = :id LIMIT 1');
        $sStmt->execute(['id' => $userId]);
    } else {
        $sStmt = $pdo->prepare('SELECT * FROM students WHERE student_no = :sno LIMIT 1');
        $sStmt->execute(['sno' => $studentNo]);
    }
    $student = $sStmt->fetch();

    if (!$student) {
        respond(404, ['success' => false, 'message' => 'Student not found.']);
    }

    $requiredHours = floatval($student['required_hours'] ?? 486);
    $sNo = $student['student_no'];
    $sId = (int)$student['id'];

    // Fetch logs
    $lStmt = $pdo->prepare('SELECT * FROM attendance_logs WHERE user_id = :uid OR student_no = :sno ORDER BY log_date DESC, time_in DESC');
    $lStmt->execute(['uid' => $sId, 'sno' => $sNo]);
    $logs = $lStmt->fetchAll();

    $accumulatedHours = 0.0;
    $todayLog = null;
    $isClockedIn = false;
    $todayStr = date('Y-m-d');
    $distinctDates = [];

    foreach ($logs as $log) {
        if ($log['status'] === 'completed') {
            $accumulatedHours += floatval($log['total_hours']);
        }
        $distinctDates[$log['log_date']] = true;
        if ($log['log_date'] === $todayStr && $todayLog === null) {
            $todayLog = $log;
            if ($log['status'] === 'ongoing') {
                $isClockedIn = true;
            }
        }
    }

    $accumulatedHours = round($accumulatedHours, 2);
    $remainingHours = max(0, round($requiredHours - $accumulatedHours, 2));
    $completionPercent = $requiredHours > 0 ? round(($accumulatedHours / $requiredHours) * 100, 1) : 0;
    if ($completionPercent > 100) $completionPercent = 100;

    // Days remaining
    $daysRemaining = 0;
    if (!empty($student['target_date'])) {
        $today = new DateTime();
        $target = new DateTime($student['target_date']);
        $diff = $today->diff($target);
        $daysRemaining = $diff->invert ? 0 : $diff->days;
    }

    // Task counts
    $tStmt = $pdo->prepare('SELECT status, COUNT(*) as cnt FROM tasks WHERE user_id = :uid OR student_no = :sno GROUP BY status');
    $tStmt->execute(['uid' => $sId, 'sno' => $sNo]);
    $taskCounts = $tStmt->fetchAll();

    $pendingTasks = 0;
    $completedTasks = 0;
    foreach ($taskCounts as $tc) {
        if ($tc['status'] === 'completed') {
            $completedTasks += (int)$tc['cnt'];
        } else {
            $pendingTasks += (int)$tc['cnt'];
        }
    }

    if ($todayLog) {
        $todayLog['id'] = (int)$todayLog['id'];
        $todayLog['user_id'] = (int)($todayLog['user_id'] ?? $sId);
        $todayLog['total_hours'] = floatval($todayLog['total_hours'] ?? 0);
    }

    respond(200, [
        'success' => true,
        'stats' => [
            'accumulated_hours'  => $accumulatedHours,
            'required_hours'     => (int)$requiredHours,
            'remaining_hours'    => $remainingHours,
            'completion_percent' => $completionPercent,
            'days_remaining'     => $daysRemaining,
            'total_days_logged'  => count($distinctDates),
            'is_clocked_in'      => $isClockedIn,
            'pending_tasks'      => $pendingTasks,
            'completed_tasks'    => $completedTasks,
            'today_log'          => $todayLog,
        ]
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

