<?php
declare(strict_types=1);

require_once __DIR__ . '/../../common.php';

$professorId = query_int('professor_id');
$section = trim((string)($_GET['section'] ?? ''));
$status = trim((string)($_GET['status'] ?? 'all'));
$reportType = trim((string)($_GET['report_type'] ?? 'all'));
$search = trim((string)($_GET['search'] ?? ''));

try {
    $pdo = get_db_connection();

    $where = [];
    $params = [];

    // Filter by assigned professor / coordinator or section
    if ($professorId && $section !== '' && $section !== 'all') {
        $where[] = '(r.professor_id = :pid OR r.course_section = :sec OR s.course_section = :sec2)';
        $params['pid'] = $professorId;
        $params['sec'] = $section;
        $params['sec2'] = $section;
    } elseif ($professorId) {
        $where[] = '(r.professor_id = :pid OR s.professor_id = :pid2)';
        $params['pid'] = $professorId;
        $params['pid2'] = $professorId;
    } elseif ($section !== '' && $section !== 'all') {
        $where[] = '(r.course_section = :sec OR s.course_section = :sec2)';
        $params['sec'] = $section;
        $params['sec2'] = $section;
    }

    if ($status !== '' && $status !== 'all') {
        $where[] = 'r.status = :status';
        $params['status'] = strtolower($status);
    }

    if ($reportType !== '' && $reportType !== 'all') {
        $where[] = 'r.report_type = :rtype';
        $params['rtype'] = $reportType;
    }

    if ($search !== '') {
        $where[] = '(r.student_name LIKE :q OR r.student_no LIKE :q2 OR r.report_title LIKE :q3 OR s.company_name LIKE :q4)';
        $param = '%' . $search . '%';
        $params['q'] = $param;
        $params['q2'] = $param;
        $params['q3'] = $param;
        $params['q4'] = $param;
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT 
                r.*,
                COALESCE(s.company_name, 'Assigned Company') as student_company,
                s.email as student_email,
                s.required_hours,
                s.target_date
            FROM submitted_reports r
            LEFT JOIN students s ON r.user_id = s.id
            {$whereSql} 
            ORDER BY r.id DESC 
            LIMIT 150";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalReports = count($reports);
    $timeLogCount = 0;
    $summaryCount = 0;
    $pendingReview = 0;
    $approvedCount = 0;

    foreach ($reports as &$r) {
        $r['id'] = (int)$r['id'];
        $r['user_id'] = (int)$r['user_id'];
        $r['professor_id'] = $r['professor_id'] !== null ? (int)$r['professor_id'] : null;
        $r['total_hours'] = floatval($r['total_hours']);
        $r['total_days'] = intval($r['total_days']);
        $r['required_hours'] = floatval($r['required_hours'] ?? 500);

        if (!empty($r['report_data']) && is_string($r['report_data'])) {
            $r['report_details'] = json_decode($r['report_data'], true);
        } else {
            $r['report_details'] = null;
        }

        // AUTO-FALLBACK: If logs are empty or missing in report_details, retrieve actual attendance logs from database
        if (empty($r['report_details']['logs']) || !is_array($r['report_details']['logs']) || count($r['report_details']['logs']) === 0) {
            $logStmt = $pdo->prepare('SELECT id, user_id, student_no, log_date, time_in, time_out, total_hours, location_name, activity_notes, status FROM attendance_logs WHERE user_id = :uid ORDER BY log_date ASC, time_in ASC');
            $logStmt->execute(['uid' => $r['user_id']]);
            $dbLogs = $logStmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($dbLogs)) {
                if (!is_array($r['report_details'])) {
                    $r['report_details'] = [];
                }
                $r['report_details']['logs'] = $dbLogs;
                if (empty($r['total_days'])) {
                    $r['total_days'] = count($dbLogs);
                }
            }
        }

        // AUTO-FALLBACK: If tasks are empty or missing in summary report, retrieve student tasks from database
        if ($r['report_type'] === 'Summary' && (empty($r['report_details']['tasks']) || count($r['report_details']['tasks']) === 0)) {
            $taskStmt = $pdo->prepare('SELECT id, user_id, title, description, deadline, priority, status FROM tasks WHERE user_id = :uid ORDER BY id ASC');
            $taskStmt->execute(['uid' => $r['user_id']]);
            $dbTasks = $taskStmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($dbTasks)) {
                if (!is_array($r['report_details'])) {
                    $r['report_details'] = [];
                }
                $r['report_details']['tasks'] = $dbTasks;
            }
        }

        if ($r['report_type'] === 'Time Log') {
            $timeLogCount++;
        } else {
            $summaryCount++;
        }

        if ($r['status'] === 'submitted') {
            $pendingReview++;
        } elseif ($r['status'] === 'approved') {
            $approvedCount++;
        }
    }

    respond(200, [
        'success' => true,
        'count'   => $totalReports,
        'stats'   => [
            'total_reports'  => $totalReports,
            'time_log_count' => $timeLogCount,
            'summary_count'  => $summaryCount,
            'pending_review' => $pendingReview,
            'approved_count' => $approvedCount,
            'total'          => $totalReports,
            'time_log'       => $timeLogCount,
            'summary'        => $summaryCount,
            'submitted'      => $pendingReview,
            'approved'       => $approvedCount,
        ],
        'reports' => $reports,
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
