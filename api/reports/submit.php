<?php
declare(strict_types=1);

require_once __DIR__ . '/../common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$body = read_json_body();
$userId = filter_var($body['user_id'] ?? null, FILTER_VALIDATE_INT);
$reportTitle = trim((string)($body['report_title'] ?? ''));
$reportType = !empty($body['report_type']) ? trim((string)$body['report_type']) : 'Time Log';
$reportDate = !empty($body['report_date']) ? trim((string)$body['report_date']) : date('Y-m-d');
$totalHours = isset($body['total_hours']) ? floatval($body['total_hours']) : 0.00;
$totalDays = isset($body['total_days']) ? intval($body['total_days']) : 0;
$reportData = isset($body['report_data']) ? (is_string($body['report_data']) ? $body['report_data'] : json_encode($body['report_data'])) : null;
$notes = !empty($body['notes']) ? trim((string)$body['notes']) : null;
$fileName = !empty($body['file_name']) ? trim((string)$body['file_name']) : null;
$fileUrl = !empty($body['file_url']) ? trim((string)$body['file_url']) : null;

if (!$userId) {
    respond(400, ['success' => false, 'message' => 'User ID is required.']);
}
if ($reportTitle === '') {
    $reportTitle = ($reportType === 'Summary' ? 'OJT Progress Summary (' : 'DTR Time Log (') . $reportDate . ')';
}

// Handle Base64 file upload if provided
if (!empty($body['file_data']) && is_string($body['file_data'])) {
    $saved = save_base64_upload(
        $body['file_data'],
        'reports',
        'report_',
        (string)($body['file_name'] ?? ''),
        'pdf'
    );
    if ($saved !== null) {
        $fileUrl = $saved['url'];
        if (!$fileName) {
            $fileName = $saved['name'];
        }
    }
}

try {
    $pdo = get_db_connection();

    // Fetch student info
    $stmt = $pdo->prepare('SELECT id, student_no, full_name, course_section, school_campus, professor_id, adviser_name, adviser_email FROM students WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $student = $stmt->fetch();

    if (!$student) {
        respond(404, ['success' => false, 'message' => 'Student record not found.']);
    }

    $studentNo = $student['student_no'];
    $studentName = $student['full_name'];
    $courseSection = $student['course_section'] ?? 'BSIT 4-A';
    $schoolCampus = $student['school_campus'] ?? 'NEUST - Sumacab Campus';
    $professorId = !empty($student['professor_id']) ? (int)$student['professor_id'] : null;
    $adviserName = $student['adviser_name'] ?? 'OJT Coordinator';
    $adviserEmail = $student['adviser_email'] ?? 'adviser@neust.edu.ph';

    // Ensure report_data always contains student's real attendance logs
    $dataArr = !empty($reportData) ? json_decode($reportData, true) : [];
    if (!is_array($dataArr)) {
        $dataArr = [];
    }
    if (empty($dataArr['logs']) || !is_array($dataArr['logs']) || count($dataArr['logs']) === 0) {
        $lStmt = $pdo->prepare('SELECT id, user_id, student_no, log_date, time_in, time_out, total_hours, location_name, activity_notes, status FROM attendance_logs WHERE user_id = :uid ORDER BY log_date ASC, time_in ASC');
        $lStmt->execute(['uid' => $userId]);
        $autoLogs = $lStmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($autoLogs)) {
            $dataArr['logs'] = $autoLogs;
            if ($totalDays <= 0) {
                $totalDays = count($autoLogs);
            }
            if ($totalHours <= 0) {
                $thSum = 0.0;
                foreach ($autoLogs as $al) {
                    $thSum += floatval($al['total_hours'] ?? 0);
                }
                $totalHours = round($thSum, 2);
            }
        }
    }
    if ($reportType === 'Summary' && (empty($dataArr['tasks']) || !is_array($dataArr['tasks']) || count($dataArr['tasks']) === 0)) {
        $tStmt = $pdo->prepare('SELECT id, user_id, title, description, deadline, priority, status FROM tasks WHERE user_id = :uid ORDER BY id ASC');
        $tStmt->execute(['uid' => $userId]);
        $autoTasks = $tStmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($autoTasks)) {
            $dataArr['tasks'] = $autoTasks;
        }
    }
    $reportData = json_encode($dataArr);

    $reportIdParam = filter_var($body['report_id'] ?? null, FILTER_VALIDATE_INT);
    $reportId = null;

    if ($reportIdParam && $reportIdParam > 0) {
        // Explicitly updating an existing report submission
        $checkStmt = $pdo->prepare('SELECT id FROM submitted_reports WHERE id = :id AND user_id = :uid LIMIT 1');
        $checkStmt->execute(['id' => $reportIdParam, 'uid' => $userId]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            $upStmt = $pdo->prepare(
                'UPDATE submitted_reports 
                 SET report_title = :title, report_type = :type, total_hours = :th, total_days = :td, 
                     report_data = :rd, file_url = COALESCE(:fu, file_url), file_name = COALESCE(:fn, file_name), 
                     notes = COALESCE(:notes, notes), status = "submitted", created_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $upStmt->execute([
                'title' => $reportTitle,
                'type'  => $reportType,
                'th'    => $totalHours,
                'td'    => $totalDays,
                'rd'    => $reportData,
                'fu'    => $fileUrl,
                'fn'    => $fileName,
                'notes' => $notes,
                'id'    => $existing['id'],
            ]);
            $reportId = (int)$existing['id'];
        }
    }

    // If no report_id passed or not found, ALWAYS create a fresh submission
    if (!$reportId) {
        $ins = $pdo->prepare(
            'INSERT INTO submitted_reports 
             (user_id, student_no, student_name, course_section, school_campus, professor_id, adviser_name, adviser_email, 
              report_title, report_type, report_date, total_hours, total_days, report_data, file_url, file_name, notes, status)
             VALUES (:uid, :sno, :sname, :cs, :sc, :pid, :aname, :aemail, :title, :type, :rdate, :th, :td, :rd, :fu, :fn, :notes, "submitted")'
        );
        $ins->execute([
            'uid'    => $userId,
            'sno'    => $studentNo,
            'sname'  => $studentName,
            'cs'     => $courseSection,
            'sc'     => $schoolCampus,
            'pid'    => $professorId,
            'aname'  => $adviserName,
            'aemail' => $adviserEmail,
            'title'  => $reportTitle,
            'type'   => $reportType,
            'rdate'  => $reportDate,
            'th'     => $totalHours,
            'td'     => $totalDays,
            'rd'     => $reportData,
            'fu'     => $fileUrl,
            'fn'     => $fileName,
            'notes'  => $notes,
        ]);
        $reportId = (int)$pdo->lastInsertId();
    }

    $fetch = $pdo->prepare('SELECT * FROM submitted_reports WHERE id = :id');
    $fetch->execute(['id' => $reportId]);
    $savedReport = $fetch->fetch();

    respond(200, [
        'success' => true,
        'message' => "Report submitted successfully to {$adviserName}!",
        'report'  => $savedReport,
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
