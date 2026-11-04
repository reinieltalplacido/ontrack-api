<?php
// backend/api/attendance/clock_out.php
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$user_id        = intval($input['user_id'] ?? ($_POST['user_id'] ?? 0));
$activity_notes = trim($input['activity_notes'] ?? ($_POST['activity_notes'] ?? ''));

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid user_id is required.']);
    exit();
}

try {
    // 1. Find today's active clock-in log
    $stmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE user_id = :uid AND status = 'ongoing' ORDER BY id DESC LIMIT 1");
    $stmt->execute(['uid' => $user_id]);
    $log = $stmt->fetch();

    if (!$log) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No active clock-in found. Please clock in first.']);
        exit();
    }

    // 2. Compute rendered hours
    $timeIn = new DateTime($log['log_date'] . ' ' . $log['time_in']);
    $timeNow = new DateTime();
    $diffSeconds = $timeNow->getTimestamp() - $timeIn->getTimestamp();
    $renderedHours = round(max(0, $diffSeconds / 3600), 2);

    // If clocking in and out in seconds for testing, guarantee at least 0.1 hr or actual
    if ($renderedHours <= 0) {
        $renderedHours = 0.05;
    }

    // 3. Update Log
    $updateStmt = $pdo->prepare("UPDATE attendance_logs SET 
        time_out = CURRENT_TIME(),
        total_hours = :hours,
        activity_notes = :notes,
        status = 'completed'
        WHERE id = :id
    ");

    $updateStmt->execute([
        'hours' => $renderedHours,
        'notes' => $activity_notes,
        'id'    => $log['id']
    ]);

    // 4. Return updated record and new total accumulated hours
    $fetchStmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE id = :id");
    $fetchStmt->execute(['id' => $log['id']]);
    $updatedLog = $fetchStmt->fetch();

    $totStmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours), 0) AS total FROM attendance_logs WHERE user_id = :uid AND status = 'completed'");
    $totStmt->execute(['uid' => $user_id]);
    $totalAccumulated = floatval($totStmt->fetch()['total']);

    echo json_encode([
        'success'           => true,
        'message'           => 'Clock-out recorded successfully!',
        'rendered_today'    => $renderedHours,
        'total_accumulated' => $totalAccumulated,
        'log'               => $updatedLog
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
