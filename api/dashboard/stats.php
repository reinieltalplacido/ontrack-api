<?php
// backend/api/dashboard/stats.php
require_once __DIR__ . '/../../config/db.php';

$user_id = intval($_GET['user_id'] ?? 0);

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid user_id parameter.']);
    exit();
}

try {
    // 1. Get User Profile & Targets
    $uStmt = $pdo->prepare("SELECT id, full_name, student_id, required_hours, target_date, company_name FROM users WHERE id = :id");
    $uStmt->execute(['id' => $user_id]);
    $user = $uStmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
        exit();
    }

    $required_hours = floatval($user['required_hours'] ?? 486);

    // 2. Compute Accumulated Completed Hours
    $hStmt = $pdo->prepare("SELECT COALESCE(SUM(total_hours), 0) AS total_accumulated, COUNT(id) AS total_days FROM attendance_logs WHERE user_id = :id AND status = 'completed'");
    $hStmt->execute(['id' => $user_id]);
    $hRow = $hStmt->fetch();
    $accumulated_hours = floatval($hRow['total_accumulated']);
    $total_days = intval($hRow['total_days']);

    $remaining_hours = max(0, $required_hours - $accumulated_hours);
    $completion_percent = $required_hours > 0 ? round(($accumulated_hours / $required_hours) * 100, 1) : 0;
    if ($completion_percent > 100) $completion_percent = 100;

    // 3. Days Remaining until Target Completion Date
    $days_remaining = 0;
    if (!empty($user['target_date'])) {
        $today = new DateTime();
        $target = new DateTime($user['target_date']);
        $diff = $today->diff($target);
        $days_remaining = $diff->invert ? 0 : $diff->days;
    }

    // 4. Check Today's Active Attendance Status (Clocked in?)
    $todayStmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE user_id = :id AND log_date = CURRENT_DATE() ORDER BY id DESC LIMIT 1");
    $todayStmt->execute(['id' => $user_id]);
    $todayLog = $todayStmt->fetch();

    $is_clocked_in = false;
    if ($todayLog && $todayLog['status'] === 'ongoing') {
        $is_clocked_in = true;
    }

    // 5. Tasks Stats
    $tStmt = $pdo->prepare("SELECT 
        COUNT(CASE WHEN status != 'completed' THEN 1 END) AS pending_tasks,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) AS completed_tasks
    FROM tasks WHERE user_id = :id");
    $tStmt->execute(['id' => $user_id]);
    $tasksRow = $tStmt->fetch();

    echo json_encode([
        'success' => true,
        'stats' => [
            'accumulated_hours'  => $accumulated_hours,
            'required_hours'     => $required_hours,
            'remaining_hours'    => $remaining_hours,
            'completion_percent' => $completion_percent,
            'days_remaining'     => $days_remaining,
            'total_days_logged'  => $total_days,
            'is_clocked_in'      => $is_clocked_in,
            'pending_tasks'      => intval($tasksRow['pending_tasks'] ?? 0),
            'completed_tasks'    => intval($tasksRow['completed_tasks'] ?? 0),
            'today_log'          => $todayLog ?: null,
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
