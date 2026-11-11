<?php
declare(strict_types=1);

require_once __DIR__ . '/../common.php';

$userId = query_int('user_id');
$studentNo = trim((string)($_GET['student_no'] ?? ''));

if (!$userId && $studentNo === '') {
    respond(400, ['success' => false, 'message' => 'user_id or student_no query parameter is required.']);
}

try {
    $pdo = get_db_connection();

    // Ensure columns exist
    try {
        $pdo->exec("ALTER TABLE submitted_reports ADD COLUMN file_url VARCHAR(255) NULL AFTER report_data");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE submitted_reports ADD COLUMN file_name VARCHAR(255) NULL AFTER file_url");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE submitted_reports ADD COLUMN notes TEXT NULL AFTER file_name");
    } catch (Exception $e) {}

    $stmt = $pdo->prepare('SELECT id, user_id, student_no, student_name, course_section, school_campus, adviser_name, adviser_email, report_title, report_type, report_date, total_hours, total_days, file_url, file_name, notes, status, reviewed_at, reviewed_by, created_at FROM submitted_reports WHERE user_id = :uid OR student_no = :sno ORDER BY id DESC');
    $stmt->execute(['uid' => $userId ?? 0, 'sno' => $studentNo]);
    $reports = $stmt->fetchAll();

    foreach ($reports as &$r) {
        $r['id'] = (int)$r['id'];
        $r['user_id'] = (int)$r['user_id'];
        $r['total_hours'] = floatval($r['total_hours']);
        $r['total_days'] = intval($r['total_days']);
    }

    respond(200, [
        'success' => true,
        'count'   => count($reports),
        'reports' => $reports,
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
