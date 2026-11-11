<?php
declare(strict_types=1);

require_once __DIR__ . '/../../common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$body = read_json_body();
$reportId = filter_var($body['report_id'] ?? null, FILTER_VALIDATE_INT);
$status = in_array($body['status'] ?? '', ['reviewed', 'approved', 'submitted']) ? $body['status'] : 'reviewed';
$reviewedBy = trim((string)($body['reviewed_by'] ?? 'OJT Coordinator'));

if (!$reportId) {
    respond(400, ['success' => false, 'message' => 'Report ID is required.']);
}

try {
    $pdo = get_db_connection();

    $stmt = $pdo->prepare('SELECT id, student_name, report_title FROM submitted_reports WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $reportId]);
    $report = $stmt->fetch();

    if (!$report) {
        respond(404, ['success' => false, 'message' => 'Report not found.']);
    }

    $up = $pdo->prepare(
        'UPDATE submitted_reports 
         SET status = :st, reviewed_at = CURRENT_TIMESTAMP, reviewed_by = :rb 
         WHERE id = :id'
    );
    $up->execute([
        'st' => $status,
        'rb' => $reviewedBy,
        'id' => $reportId,
    ]);

    respond(200, [
        'success' => true,
        'message' => "Report '{$report['report_title']}' has been marked as {$status} by {$reviewedBy}.",
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
