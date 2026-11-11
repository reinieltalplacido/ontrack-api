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

    $stmt = $pdo->prepare('SELECT * FROM attendance_logs WHERE user_id = :uid OR student_no = :sno ORDER BY log_date DESC, time_in ASC');
    $stmt->execute(['uid' => $userId ?? 0, 'sno' => $studentNo]);
    $rawLogs = $stmt->fetchAll();

    // Group logs by calendar date so multiple shifts (e.g. morning + afternoon) are combined into 1 daily record with summed hours
    $grouped = [];
    $totalHours = 0.0;

    foreach ($rawLogs as $row) {
        $rowId = (int)$row['id'];
        $d = $row['log_date'];
        $rowHours = floatval($row['total_hours'] ?? 0);
        $isCompleted = ($row['status'] === 'completed');

        if ($isCompleted) {
            $totalHours += $rowHours;
        }

        if (!isset($grouped[$d])) {
            $grouped[$d] = [
                'id'             => $rowId,
                'user_id'        => (int)($row['user_id'] ?? $userId),
                'student_no'     => $row['student_no'],
                'log_date'       => $d,
                'time_in'        => $row['time_in'],
                'time_out'       => $row['time_out'],
                'total_hours'    => $rowHours,
                'latitude'       => $row['latitude'] !== null ? floatval($row['latitude']) : null,
                'longitude'      => $row['longitude'] !== null ? floatval($row['longitude']) : null,
                'location_name'  => $row['location_name'],
                'photo_url'      => $row['photo_url'],
                'activity_notes' => trim((string)($row['activity_notes'] ?? '')),
                'status'         => $row['status'],
                'shifts_count'   => 1,
                'shifts'         => [[
                    'id'          => $rowId,
                    'time_in'     => $row['time_in'],
                    'time_out'    => $row['time_out'],
                    'total_hours' => $rowHours,
                    'status'      => $row['status'],
                    'notes'       => trim((string)($row['activity_notes'] ?? '')),
                ]],
            ];
        } else {
            // Additional shift on the same date! Consolidate and sum hours
            $grouped[$d]['shifts_count']++;
            $grouped[$d]['total_hours'] = round($grouped[$d]['total_hours'] + $rowHours, 2);

            // Keep earliest time_in for the day
            if ($row['time_in'] < $grouped[$d]['time_in']) {
                $grouped[$d]['time_in'] = $row['time_in'];
            }

            // Status: if any session today is ongoing, the whole date is ongoing
            if ($row['status'] === 'ongoing' || $grouped[$d]['status'] === 'ongoing') {
                $grouped[$d]['status'] = 'ongoing';
                $grouped[$d]['time_out'] = null; // Currently active
            } else {
                if ($row['time_out'] && (!$grouped[$d]['time_out'] || $row['time_out'] > $grouped[$d]['time_out'])) {
                    $grouped[$d]['time_out'] = $row['time_out'];
                }
            }

            // Append accomplishment notes if distinct
            $newNotes = trim((string)($row['activity_notes'] ?? ''));
            if ($newNotes !== '' && $newNotes !== 'Duty shift completed') {
                if ($grouped[$d]['activity_notes'] === '' || $grouped[$d]['activity_notes'] === 'Duty shift completed') {
                    $grouped[$d]['activity_notes'] = $newNotes;
                } elseif (strpos($grouped[$d]['activity_notes'], $newNotes) === false) {
                    $grouped[$d]['activity_notes'] .= ' | ' . $newNotes;
                }
            }

            $grouped[$d]['shifts'][] = [
                'id'          => $rowId,
                'time_in'     => $row['time_in'],
                'time_out'    => $row['time_out'],
                'total_hours' => $rowHours,
                'status'      => $row['status'],
                'notes'       => trim((string)($row['activity_notes'] ?? '')),
            ];
        }
    }

    $logs = array_values($grouped);

    respond(200, [
        'success'     => true,
        'count'       => count($logs),
        'total_hours' => round($totalHours, 2),
        'logs'        => $logs,
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

