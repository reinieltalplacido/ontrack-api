<?php
declare(strict_types=1);

require_once __DIR__ . '/../common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$input = read_json_body();
$userId = filter_var($input['user_id'] ?? null, FILTER_VALIDATE_INT);
$studentNo = trim((string)($input['student_no'] ?? ''));
$notes = trim((string)($input['activity_notes'] ?? ''));
if ($notes === '') {
    $notes = 'Duty shift completed';
}

if (!$userId && $studentNo === '') {
    respond(400, ['success' => false, 'message' => 'user_id or student_no is required.']);
}

try {
    $pdo = get_db_connection();

    // Find active ongoing log
    $stmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE (user_id = :uid OR student_no = :sno) AND status = 'ongoing' ORDER BY id DESC LIMIT 1");
    $stmt->execute(['uid' => $userId ?? 0, 'sno' => $studentNo]);
    $activeLog = $stmt->fetch();

    if (!$activeLog) {
        respond(404, ['success' => false, 'message' => 'No active clock-in found. Please clock in first.']);
    }

    $timeIn = new DateTime($activeLog['log_date'] . ' ' . $activeLog['time_in']);
    $timeNow = new DateTime();
    $diffSeconds = $timeNow->getTimestamp() - $timeIn->getTimestamp();
    $renderedHours = round(max(0, $diffSeconds / 3600), 2);
    if ($renderedHours <= 0) {
        $renderedHours = 0.25; // demo fallback minimum for testing
    }

    $upStmt = $pdo->prepare("UPDATE attendance_logs SET time_out = CURRENT_TIME(), total_hours = :hrs, activity_notes = :notes, status = 'completed' WHERE id = :id");
    $upStmt->execute([
        'hrs'   => $renderedHours,
        'notes' => $notes,
        'id'    => $activeLog['id'],
    ]);

    $logStmt = $pdo->prepare('SELECT * FROM attendance_logs WHERE id = :id');
    $logStmt->execute(['id' => $activeLog['id']]);
    $updatedLog = $logStmt->fetch();

    $updatedLog['id'] = (int)$updatedLog['id'];
    $updatedLog['user_id'] = (int)($updatedLog['user_id'] ?? $userId);
    $updatedLog['total_hours'] = floatval($updatedLog['total_hours']);

    // Compute total accumulated
    $totStmt = $pdo->prepare("SELECT SUM(total_hours) as total FROM attendance_logs WHERE (user_id = :uid OR student_no = :sno) AND status = 'completed'");
    $totStmt->execute(['uid' => $updatedLog['user_id'], 'sno' => $updatedLog['student_no']]);
    $totalAccumulated = round(floatval($totStmt->fetch()['total'] ?? 0), 2);

    respond(200, [
        'success'           => true,
        'message'           => 'Clock-out recorded successfully!',
        'rendered_today'    => $renderedHours,
        'total_accumulated' => $totalAccumulated,
        'log'               => $updatedLog,
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

