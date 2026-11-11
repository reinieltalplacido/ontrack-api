<?php
declare(strict_types=1);

require_once __DIR__ . '/../common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$body = read_json_body();
$userId = filter_var($body['user_id'] ?? ($body['id'] ?? null), FILTER_VALIDATE_INT);
$studentNo = trim((string)($body['student_id'] ?? ($body['student_no'] ?? '')));

if (!$userId && $studentNo === '') {
    respond(400, ['success' => false, 'message' => 'User ID or Student ID is required.']);
}

try {
    $pdo = get_db_connection();

    // Find student
    if ($userId) {
        $stmt = $pdo->prepare('SELECT * FROM students WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM students WHERE student_no = :sno LIMIT 1');
        $stmt->execute(['sno' => $studentNo]);
    }
    $current = $stmt->fetch();

    if (!$current) {
        respond(404, ['success' => false, 'message' => 'Student not found.']);
    }

    $fullName       = trim((string)($body['full_name'] ?? $current['full_name']));
    $newStudentNo   = trim((string)($body['student_no'] ?? ($body['student_id'] ?? $current['student_no'])));
    $email          = trim((string)($body['email'] ?? $current['email']));
    $courseSection  = trim((string)($body['course_section'] ?? ($current['course_section'] ?? 'BSIT 4-A')));
    $schoolCampus   = trim((string)($body['school_campus'] ?? ($current['school_campus'] ?? 'NEUST - Sumacab Campus')));
    $companyName    = trim((string)($body['company_name'] ?? $current['company_name']));
    $companyAddress = trim((string)($body['company_address'] ?? ($current['company_address'] ?? '')));
    $trainerName    = trim((string)($body['trainer_name'] ?? ($current['trainer_name'] ?? '')));
    $adviserName    = trim((string)($body['adviser_name'] ?? $current['adviser_name']));
    $adviserEmail   = trim((string)($body['adviser_email'] ?? $current['adviser_email']));

    // Start Date Constraints
    $today = date('Y-m-d');
    $existingStartDate = !empty($current['start_date']) ? trim((string)$current['start_date']) : null;
    $newStartDate = !empty($body['start_date']) ? trim((string)$body['start_date']) : $existingStartDate;

    if (!empty($existingStartDate) && $existingStartDate <= $today) {
        // OJT has already started -> locked and cannot be changed
        $startDate = $existingStartDate;
    } else {
        // OJT has not started yet -> can be edited to today or future (no backdating)
        if (!empty($newStartDate)) {
            if ($newStartDate < $today) {
                respond(400, [
                    'success' => false,
                    'message' => 'Start date cannot be set in the past. Only today or future dates are allowed.'
                ]);
            }
            $startDate = $newStartDate;
        } else {
            $startDate = $existingStartDate;
        }
    }

    $targetDate     = !empty($body['target_date']) ? trim((string)$body['target_date']) : $current['target_date'];
    if (!empty($startDate) && !empty($targetDate) && $targetDate < $startDate) {
        respond(400, [
            'success' => false,
            'message' => 'Target end date cannot be earlier than the start date.'
        ]);
    }

    $requiredHours  = isset($body['required_hours']) ? intval($body['required_hours']) : intval($current['required_hours']);

    $passwordSql = '';
    $params = [
        'fn'  => $fullName,
        'sno' => $newStudentNo,
        'em'  => $email,
        'cs'  => $courseSection,
        'sc'  => $schoolCampus,
        'cn'  => $companyName,
        'ca'  => $companyAddress,
        'tn'  => $trainerName,
        'an'  => $adviserName,
        'ae'  => $adviserEmail,
        'sd'  => $startDate,
        'td'  => $targetDate,
        'rh'  => $requiredHours,
        'id'  => $current['id'],
    ];

    if (!empty($body['new_password'])) {
        $passwordSql = ', password = :pwd';
        $params['pwd'] = password_hash(trim((string)$body['new_password']), PASSWORD_BCRYPT);
    }

    $upStmt = $pdo->prepare(
        "UPDATE students
         SET full_name = :fn, student_no = :sno, email = :em, course_section = :cs, school_campus = :sc, 
             company_name = :cn, company_address = :ca, trainer_name = :tn, adviser_name = :an, adviser_email = :ae, 
             start_date = :sd, target_date = :td, required_hours = :rh {$passwordSql}
         WHERE id = :id"
    );
    $upStmt->execute($params);

    $fetchUpdated = $pdo->prepare('SELECT * FROM students WHERE id = :id');
    $fetchUpdated->execute(['id' => $current['id']]);
    $updated = $fetchUpdated->fetch();

    respond(200, [
        'success' => true,
        'message' => 'Profile updated successfully.',
        'user'    => format_student_user($updated),
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
