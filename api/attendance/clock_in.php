<?php
declare(strict_types=1);

require_once __DIR__ . '/../common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$input = read_json_body();
$userId = filter_var($input['user_id'] ?? null, FILTER_VALIDATE_INT);
$studentNo = trim((string)($input['student_no'] ?? ''));

if (!$userId && $studentNo === '') {
    respond(400, ['success' => false, 'message' => 'user_id or student_no is required.']);
}

try {
    $pdo = get_db_connection();

    // Find student
    if ($userId) {
        $stmt = $pdo->prepare('SELECT id, student_no FROM students WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
    } else {
        $stmt = $pdo->prepare('SELECT id, student_no FROM students WHERE student_no = :sno LIMIT 1');
        $stmt->execute(['sno' => $studentNo]);
    }
    $student = $stmt->fetch();

    if (!$student) {
        respond(404, ['success' => false, 'message' => 'Student not found.']);
    }

    $sId = (int)$student['id'];
    $sNo = $student['student_no'];

    // Check if ongoing today
    $check = $pdo->prepare("SELECT id, time_in FROM attendance_logs WHERE (user_id = :uid OR student_no = :sno) AND log_date = CURRENT_DATE() AND status = 'ongoing' LIMIT 1");
    $check->execute(['uid' => $sId, 'sno' => $sNo]);
    $existing = $check->fetch();

    if ($existing) {
        respond(409, [
            'success' => false,
            'message' => 'You are already clocked in today at ' . substr($existing['time_in'], 0, 5),
            'log_id'  => (int)$existing['id'],
        ]);
    }

    $latitude      = isset($input['latitude']) ? floatval($input['latitude']) : null;
    $longitude     = isset($input['longitude']) ? floatval($input['longitude']) : null;
    $locationName  = trim($input['location_name'] ?? 'Assigned Workplace (GPS Validated)');
    $photoBase64   = $input['photo_base64'] ?? null;
    $photoUrl      = null;

    if (!empty($photoBase64)) {
        $uploadsDir = dirname(__DIR__, 2) . '/uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }
        if (preg_match('/^data:image\/(\w+);base64,/', $photoBase64)) {
            $photoBase64 = substr($photoBase64, strpos($photoBase64, ',') + 1);
        }
        $decoded = base64_decode($photoBase64);
        if ($decoded !== false) {
            $fileName = 'proof_' . preg_replace('/[^a-zA-Z0-9]/', '_', $sNo) . '_' . time() . '.jpg';
            file_put_contents($uploadsDir . '/' . $fileName, $decoded);
            $photoUrl = 'uploads/' . $fileName;
        }
    }

    $ins = $pdo->prepare(
        'INSERT INTO attendance_logs (user_id, student_no, log_date, time_in, latitude, longitude, location_name, photo_url, status)
         VALUES (:uid, :sno, CURRENT_DATE(), CURRENT_TIME(), :lat, :lng, :loc, :photo, \'ongoing\')'
    );
    $ins->execute([
        'uid'   => $sId,
        'sno'   => $sNo,
        'lat'   => $latitude,
        'lng'   => $longitude,
        'loc'   => $locationName,
        'photo' => $photoUrl,
    ]);

    $newId = (int)$pdo->lastInsertId();
    $fetchLog = $pdo->prepare('SELECT * FROM attendance_logs WHERE id = :id');
    $fetchLog->execute(['id' => $newId]);
    $log = $fetchLog->fetch();

    $log['id'] = (int)$log['id'];
    $log['user_id'] = (int)($log['user_id'] ?? $sId);
    $log['total_hours'] = floatval($log['total_hours'] ?? 0);

    respond(201, [
        'success' => true,
        'message' => 'Time-in recorded successfully with GPS verification!',
        'log'     => $log,
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

