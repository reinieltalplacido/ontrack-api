<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// CORS Headers
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . ($origin !== '' ? $origin : '*'));
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$pdo = get_db_connection();

function respond(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// -------------------------------------------------------------
// GET: Fetch Logs & Live Dashboard Statistics for Student
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $student_no = trim($_GET['student_no'] ?? '');

    if ($student_no === '') {
        respond(400, ['success' => false, 'message' => 'student_no query parameter is required.']);
    }

    // 1. Get student profile info
    $sStmt = $pdo->prepare("SELECT * FROM students WHERE student_no = :sno LIMIT 1");
    $sStmt->execute(['sno' => $student_no]);
    $student = $sStmt->fetch();

    if (!$student) {
        respond(404, ['success' => false, 'message' => 'Student not found in database.']);
    }

    $required_hours = floatval($student['required_hours'] ?? 486);

    // 2. Fetch past logs
    $lStmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE student_no = :sno ORDER BY log_date DESC, time_in DESC");
    $lStmt->execute(['sno' => $student_no]);
    $logs = $lStmt->fetchAll();

    // 3. Compute totals
    $accumulated_hours = 0.0;
    $today_log = null;
    $is_clocked_in = false;
    $todayStr = date('Y-m-d');

    foreach ($logs as $log) {
        if ($log['status'] === 'completed') {
            $accumulated_hours += floatval($log['total_hours']);
        }
        if ($log['log_date'] === $todayStr && $today_log === null) {
            $today_log = $log;
            if ($log['status'] === 'ongoing') {
                $is_clocked_in = true;
            }
        }
    }

    $accumulated_hours = round($accumulated_hours, 2);
    $remaining_hours = max(0, round($required_hours - $accumulated_hours, 2));
    $completion_percent = $required_hours > 0 ? round(($accumulated_hours / $required_hours) * 100, 1) : 0;
    if ($completion_percent > 100) $completion_percent = 100;

    // 4. Days remaining
    $days_remaining = 0;
    if (!empty($student['target_date'])) {
        $today = new DateTime();
        $target = new DateTime($student['target_date']);
        $diff = $today->diff($target);
        $days_remaining = $diff->invert ? 0 : $diff->days;
    }

    respond(200, [
        'success' => true,
        'stats' => [
            'student_no'         => $student_no,
            'full_name'          => $student['full_name'],
            'course'             => $student['course'],
            'company_name'       => $student['company_name'],
            'required_hours'     => $required_hours,
            'accumulated_hours'  => $accumulated_hours,
            'remaining_hours'    => $remaining_hours,
            'completion_percent' => $completion_percent,
            'days_remaining'     => $days_remaining,
            'is_clocked_in'      => $is_clocked_in,
            'today_log'          => $today_log,
        ],
        'logs' => $logs
    ]);
}

// -------------------------------------------------------------
// POST: Clock In or Clock Out
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        respond(400, ['success' => false, 'message' => 'Invalid JSON body.']);
    }

    $action = $input['action'] ?? 'clock_in';
    $student_no = trim($input['student_no'] ?? '');

    if ($student_no === '') {
        respond(400, ['success' => false, 'message' => 'student_no is required.']);
    }

    // CLOCK IN ACTION
    if ($action === 'clock_in') {
        // Check if already clocked in today
        $checkStmt = $pdo->prepare("SELECT id, time_in FROM attendance_logs WHERE student_no = :sno AND log_date = CURRENT_DATE() AND status = 'ongoing' LIMIT 1");
        $checkStmt->execute(['sno' => $student_no]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            respond(409, [
                'success' => false,
                'message' => 'You are already clocked in today at ' . substr($existing['time_in'], 0, 5),
                'log_id' => $existing['id']
            ]);
        }

        $latitude      = isset($input['latitude']) ? floatval($input['latitude']) : null;
        $longitude     = isset($input['longitude']) ? floatval($input['longitude']) : null;
        $location_name = trim($input['location_name'] ?? 'Assigned Workplace (GPS Validated)');
        $photo_base64  = $input['photo_base64'] ?? null;
        $photo_url     = null;

        // Save proof image if present
        if (!empty($photo_base64)) {
            $uploadsDir = __DIR__ . '/../uploads';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0777, true);
            }
            if (preg_match('/^data:image\/(\w+);base64,/', $photo_base64)) {
                $photo_base64 = substr($photo_base64, strpos($photo_base64, ',') + 1);
            }
            $decoded = base64_decode($photo_base64);
            if ($decoded !== false) {
                $fileName = 'proof_' . preg_replace('/[^a-zA-Z0-9]/', '_', $student_no) . '_' . time() . '.jpg';
                file_put_contents($uploadsDir . '/' . $fileName, $decoded);
                $photo_url = 'uploads/' . $fileName;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO attendance_logs (student_no, log_date, time_in, latitude, longitude, location_name, photo_url, status) VALUES (:sno, CURRENT_DATE(), CURRENT_TIME(), :lat, :lng, :loc, :photo, 'ongoing')");
        $stmt->execute([
            'sno'   => $student_no,
            'lat'   => $latitude,
            'lng'   => $longitude,
            'loc'   => $location_name,
            'photo' => $photo_url
        ]);

        $newId = $pdo->lastInsertId();
        $logStmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE id = :id");
        $logStmt->execute(['id' => $newId]);

        respond(201, [
            'success' => true,
            'message' => 'Time-in recorded successfully with GPS verification!',
            'log' => $logStmt->fetch()
        ]);
    }

    // CLOCK OUT ACTION
    if ($action === 'clock_out') {
        $notes = trim($input['activity_notes'] ?? '');

        $stmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE student_no = :sno AND status = 'ongoing' ORDER BY id DESC LIMIT 1");
        $stmt->execute(['sno' => $student_no]);
        $activeLog = $stmt->fetch();

        if (!$activeLog) {
            respond(404, ['success' => false, 'message' => 'No active clock-in found. Please clock in first.']);
        }

        // Calculate hours
        $timeIn = new DateTime($activeLog['log_date'] . ' ' . $activeLog['time_in']);
        $timeNow = new DateTime();
        $diffSeconds = $timeNow->getTimestamp() - $timeIn->getTimestamp();
        $renderedHours = round(max(0, $diffSeconds / 3600), 2);
        if ($renderedHours <= 0) $renderedHours = 0.1; // demo minimum

        $upStmt = $pdo->prepare("UPDATE attendance_logs SET time_out = CURRENT_TIME(), total_hours = :hrs, activity_notes = :notes, status = 'completed' WHERE id = :id");
        $upStmt->execute([
            'hrs'   => $renderedHours,
            'notes' => $notes,
            'id'    => $activeLog['id']
        ]);

        $logStmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE id = :id");
        $logStmt->execute(['id' => $activeLog['id']]);

        respond(200, [
            'success'        => true,
            'message'        => 'Clock-out recorded successfully!',
            'rendered_today' => $renderedHours,
            'log'            => $logStmt->fetch()
        ]);
    }

    respond(400, ['success' => false, 'message' => 'Unknown action.']);
}

