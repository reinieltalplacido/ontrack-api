<?php
declare(strict_types=1);

require_once __DIR__ . '/../../common.php';

$studentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$studentNo = trim((string)($_GET['student_no'] ?? ''));

if (!$studentId && $studentNo === '') {
    respond(400, ['success' => false, 'message' => 'Student ID (id) or student_no query parameter is required.']);
}

try {
    $pdo = get_db_connection();

    // 1. Fetch student info and assigned adviser
    if ($studentId) {
        $stmt = $pdo->prepare('SELECT s.*, p.full_name as adviser_full_name, p.email as adviser_full_email 
                               FROM students s 
                               LEFT JOIN professors p ON s.professor_id = p.id 
                               WHERE s.id = :id 
                               LIMIT 1');
        $stmt->execute(['id' => $studentId]);
    } else {
        $stmt = $pdo->prepare('SELECT s.*, p.full_name as adviser_full_name, p.email as adviser_full_email 
                               FROM students s 
                               LEFT JOIN professors p ON s.professor_id = p.id 
                               WHERE s.student_no = :sno 
                               LIMIT 1');
        $stmt->execute(['sno' => $studentNo]);
    }

    $student = $stmt->fetch();
    if (!$student) {
        respond(404, ['success' => false, 'message' => 'Student not found.']);
    }

    $sId = (int)$student['id'];
    $sNo = (string)$student['student_no'];
    $requiredHours = floatval($student['required_hours'] ?? 486);

    // 2. Fetch attendance logs using fast index seek (avoids unindexed OR scan)
    if ($sId > 0) {
        $lStmt = $pdo->prepare('SELECT * FROM attendance_logs WHERE user_id = :uid ORDER BY log_date DESC, time_in DESC');
        $lStmt->execute(['uid' => $sId]);
    } else {
        $lStmt = $pdo->prepare('SELECT * FROM attendance_logs WHERE student_no = :sno ORDER BY log_date DESC, time_in DESC');
        $lStmt->execute(['sno' => $sNo]);
    }
    $rawLogs = $lStmt->fetchAll();

    $accumulated = 0.0;
    $groupedLogs = [];
    foreach ($rawLogs as $log) {
        $rowId = (int)$log['id'];
        $d = $log['log_date'];
        $rowHours = floatval($log['total_hours'] ?? 0);
        if ($log['status'] === 'completed') {
            $accumulated += $rowHours;
        }

        if (!isset($groupedLogs[$d])) {
            $groupedLogs[$d] = [
                'id'             => $rowId,
                'user_id'        => (int)($log['user_id'] ?? $sId),
                'student_no'     => $sNo,
                'log_date'       => $d,
                'time_in'        => $log['time_in'],
                'time_out'       => $log['time_out'],
                'total_hours'    => $rowHours,
                'latitude'       => $log['latitude'] !== null ? floatval($log['latitude']) : null,
                'longitude'      => $log['longitude'] !== null ? floatval($log['longitude']) : null,
                'location_name'  => $log['location_name'],
                'photo_url'      => $log['photo_url'],
                'activity_notes' => trim((string)($log['activity_notes'] ?? '')),
                'status'         => $log['status'],
                'shifts_count'   => 1,
            ];
        } else {
            $groupedLogs[$d]['shifts_count']++;
            $groupedLogs[$d]['total_hours'] = round($groupedLogs[$d]['total_hours'] + $rowHours, 2);
            if ($log['time_in'] < $groupedLogs[$d]['time_in']) {
                $groupedLogs[$d]['time_in'] = $log['time_in'];
            }
            if ($log['status'] === 'ongoing' || $groupedLogs[$d]['status'] === 'ongoing') {
                $groupedLogs[$d]['status'] = 'ongoing';
                $groupedLogs[$d]['time_out'] = null;
            } else {
                if ($log['time_out'] && (!$groupedLogs[$d]['time_out'] || $log['time_out'] > $groupedLogs[$d]['time_out'])) {
                    $groupedLogs[$d]['time_out'] = $log['time_out'];
                }
            }
            $rowNotes = trim((string)($log['activity_notes'] ?? ''));
            if ($rowNotes !== '' && $rowNotes !== 'Duty shift completed') {
                if ($groupedLogs[$d]['activity_notes'] === '' || $groupedLogs[$d]['activity_notes'] === 'Duty shift completed') {
                    $groupedLogs[$d]['activity_notes'] = $rowNotes;
                } elseif (strpos($groupedLogs[$d]['activity_notes'], $rowNotes) === false) {
                    $groupedLogs[$d]['activity_notes'] .= ' | ' . $rowNotes;
                }
            }
        }
    }
    $logs = array_values($groupedLogs);
    $distinctDays = array_keys($groupedLogs);

    $accumulated = round($accumulated, 2);
    $remaining = max(0, round($requiredHours - $accumulated, 2));
    $percent = $requiredHours > 0 ? round(($accumulated / $requiredHours) * 100, 1) : 0;
    if ($percent > 100) $percent = 100;

    // 3. Fetch tasks using fast index seek
    if ($sId > 0) {
        $tStmt = $pdo->prepare('SELECT * FROM tasks WHERE user_id = :uid ORDER BY id DESC');
        $tStmt->execute(['uid' => $sId]);
    } else {
        $tStmt = $pdo->prepare('SELECT * FROM tasks WHERE student_no = :sno ORDER BY id DESC');
        $tStmt->execute(['sno' => $sNo]);
    }
    $tasks = $tStmt->fetchAll();
    foreach ($tasks as &$t) {
        $t['id'] = (int)$t['id'];
        $t['user_id'] = (int)($t['user_id'] ?? 0);
        if (!empty($t['proof_url']) && empty($t['proof_file'])) {
            $t['proof_file'] = basename($t['proof_url']);
        }
    }
    unset($t);

    respond(200, [
        'success' => true,
        'student' => [
            'id'                 => $sId,
            'student_no'         => $sNo,
            'full_name'          => $student['full_name'],
            'email'              => $student['email'] ?? ($sNo . '@student.neust.edu.ph'),
            'course'             => $student['course'] ?? 'BSIT',
            'course_section'     => $student['course_section'] ?? 'BSIT 4-A',
            'year_level'         => (int)($student['year_level'] ?? 4),
            'school_campus'      => $student['school_campus'] ?? 'NEUST - Sumacab Campus',
            'company_name'       => $student['company_name'] ?? 'Assigned Agency',
            'required_hours'     => (int)$requiredHours,
            'accumulated_hours'  => $accumulated,
            'remaining_hours'    => $remaining,
            'completion_percent' => $percent,
            'days_logged'        => count($distinctDays),
            'target_date'        => $student['target_date'] ?? '2026-12-15',
            'adviser_name'       => $student['adviser_full_name'] ?? ($student['adviser_name'] ?? 'OJT Coordinator'),
            'adviser_email'      => $student['adviser_full_email'] ?? ($student['adviser_email'] ?? 'adviser@neust.edu.ph'),
        ],
        'logs'  => $logs,
        'tasks' => $tasks,
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
