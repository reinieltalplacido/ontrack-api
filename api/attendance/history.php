<?php
// backend/api/attendance/history.php
require_once __DIR__ . '/../../config/db.php';

$user_id = intval($_GET['user_id'] ?? 0);

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid user_id parameter.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE user_id = :uid ORDER BY log_date DESC, time_in DESC");
    $stmt->execute(['uid' => $user_id]);
    $logs = $stmt->fetchAll();

    // Summary totals
    $totalHours = 0;
    foreach ($logs as $log) {
        if ($log['status'] === 'completed') {
            $totalHours += floatval($log['total_hours']);
        }
    }

    echo json_encode([
        'success'     => true,
        'count'       => count($logs),
        'total_hours' => round($totalHours, 2),
        'logs'        => $logs
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
