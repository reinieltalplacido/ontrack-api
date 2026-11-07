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

    $stmt = $pdo->prepare('SELECT * FROM attendance_logs WHERE user_id = :uid OR student_no = :sno ORDER BY log_date DESC, time_in DESC');
    $stmt->execute(['uid' => $userId ?? 0, 'sno' => $studentNo]);
    $logs = $stmt->fetchAll();

    $totalHours = 0.0;
    foreach ($logs as &$log) {
        $log['id'] = (int)$log['id'];
        $log['user_id'] = (int)($log['user_id'] ?? $userId);
        $log['total_hours'] = floatval($log['total_hours'] ?? 0);
        if ($log['status'] === 'completed') {
            $totalHours += $log['total_hours'];
        }
    }
    unset($log);

    respond(200, [
        'success'     => true,
        'count'       => count($logs),
        'total_hours' => round($totalHours, 2),
        'logs'        => $logs,
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

