<?php
// backend/api/attendance/clock_in.php
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$user_id       = intval($input['user_id'] ?? ($_POST['user_id'] ?? 0));
$latitude      = isset($input['latitude']) ? floatval($input['latitude']) : (isset($_POST['latitude']) ? floatval($_POST['latitude']) : null);
$longitude     = isset($input['longitude']) ? floatval($input['longitude']) : (isset($_POST['longitude']) ? floatval($_POST['longitude']) : null);
$location_name = trim($input['location_name'] ?? ($_POST['location_name'] ?? 'Assigned Workplace (GPS Validated)'));
$photo_base64  = $input['photo_base64'] ?? ($_POST['photo_base64'] ?? null);

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid user_id is required.']);
    exit();
}

try {
    // 1. Check if user already has an active (ongoing) clock-in today
    $checkStmt = $pdo->prepare("SELECT id, time_in FROM attendance_logs WHERE user_id = :uid AND log_date = CURRENT_DATE() AND status = 'ongoing' LIMIT 1");
    $checkStmt->execute(['uid' => $user_id]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        echo json_encode([
            'success' => false,
            'message' => 'You are already clocked in today at ' . substr($existing['time_in'], 0, 5),
            'log_id' => $existing['id']
        ]);
        exit();
    }

    // 2. Handle Photo Upload (Base64 from Capacitor Camera)
    $photo_url = null;
    if (!empty($photo_base64)) {
        $uploadsDir = __DIR__ . '/../../uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }

        // Clean base64 header if present (e.g. data:image/jpeg;base64,...)
        if (preg_match('/^data:image\/(\w+);base64,/', $photo_base64, $type)) {
            $photo_base64 = substr($photo_base64, strpos($photo_base64, ',') + 1);
        }
        $decodedData = base64_decode($photo_base64);
        if ($decodedData !== false) {
            $fileName = 'proof_' . $user_id . '_' . time() . '.jpg';
            $filePath = $uploadsDir . '/' . $fileName;
            file_put_contents($filePath, $decodedData);
            $photo_url = 'uploads/' . $fileName;
        }
    }

    // 3. Insert Clock-In Log
    $sql = "INSERT INTO attendance_logs (
        user_id, log_date, time_in, latitude, longitude, location_name, photo_url, status
    ) VALUES (
        :user_id, CURRENT_DATE(), CURRENT_TIME(), :latitude, :longitude, :location_name, :photo_url, 'ongoing'
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'user_id'       => $user_id,
        'latitude'      => $latitude,
        'longitude'     => $longitude,
        'location_name' => $location_name,
        'photo_url'     => $photo_url
    ]);

    $newLogId = $pdo->lastInsertId();

    $logStmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE id = :id");
    $logStmt->execute(['id' => $newLogId]);
    $log = $logStmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Time-in recorded successfully with location & photo proof!',
        'log'     => $log
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
