<?php
declare(strict_types=1);

require_once __DIR__ . '/../../common.php';

$date = trim((string)($_GET['date'] ?? ''));
$startDate = trim((string)($_GET['start_date'] ?? ''));
$endDate = trim((string)($_GET['end_date'] ?? ''));
$status = trim((string)($_GET['status'] ?? 'all'));
$studentId = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);
$search = trim((string)($_GET['search'] ?? ''));
$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 200;

try {
    $pdo = get_db_connection();

    $query = 'SELECT a.*, s.full_name as student_name, s.student_no, s.course_section, s.company_name as student_company FROM attendance_logs a JOIN students s ON (a.user_id = s.id OR a.student_no = s.student_no) WHERE 1=1';
    $params = [];

    if ($date !== '') {
        $query .= ' AND a.log_date = :ldate';
        $params['ldate'] = $date;
    }

    if ($startDate !== '' && $endDate !== '') {
        $query .= ' AND a.log_date >= :sdate AND a.log_date <= :edate';
        $params['sdate'] = $startDate;
        $params['edate'] = $endDate;
    }

    if ($status !== 'all' && ($status === 'ongoing' || $status === 'completed')) {
        $query .= ' AND a.status = :status';
        $params['status'] = $status;
    }

    if ($studentId) {
        $query .= ' AND (a.user_id = :uid OR s.id = :sid)';
        $params['uid'] = $studentId;
        $params['sid'] = $studentId;
    }

    if ($search !== '') {
        $query .= ' AND (s.full_name LIKE :s1 OR s.student_no LIKE :s2 OR a.location_name LIKE :s3)';
        $param = "%{$search}%";
        $params['s1'] = $param;
        $params['s2'] = $param;
        $params['s3'] = $param;
    }

    $query .= ' ORDER BY a.log_date DESC, a.time_in DESC LIMIT ' . (int)$limit;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    foreach ($logs as &$l) {
        $l['id'] = (int)$l['id'];
        $l['user_id'] = (int)$l['user_id'];
        $l['total_hours'] = floatval($l['total_hours'] ?? 0);
        $l['latitude'] = $l['latitude'] !== null ? floatval($l['latitude']) : null;
        $l['longitude'] = $l['longitude'] !== null ? floatval($l['longitude']) : null;
    }
    unset($l);

    respond(200, [
        'success' => true,
        'count'   => count($logs),
        'logs'    => $logs,
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
