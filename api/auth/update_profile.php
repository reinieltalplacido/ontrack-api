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

    $fullName      = trim((string)($body['full_name'] ?? $current['full_name']));
    $email         = trim((string)($body['email'] ?? $current['email']));
    $courseSection = trim((string)($body['course_section'] ?? ($current['course_section'] ?? 'BSIT 4-A')));
    $schoolCampus  = trim((string)($body['school_campus'] ?? ($current['school_campus'] ?? 'NEUST - Sumacab Campus')));
    $companyName   = trim((string)($body['company_name'] ?? $current['company_name']));
    $adviserName   = trim((string)($body['adviser_name'] ?? $current['adviser_name']));
    $adviserEmail  = trim((string)($body['adviser_email'] ?? $current['adviser_email']));
    $targetDate    = !empty($body['target_date']) ? $body['target_date'] : $current['target_date'];
    $requiredHours = isset($body['required_hours']) ? intval($body['required_hours']) : intval($current['required_hours']);

    $upStmt = $pdo->prepare(
        'UPDATE students
         SET full_name = :fn, email = :em, course_section = :cs, school_campus = :sc, company_name = :cn, adviser_name = :an, adviser_email = :ae, target_date = :td, required_hours = :rh
         WHERE id = :id'
    );
    $upStmt->execute([
        'fn' => $fullName,
        'em' => $email,
        'cs' => $courseSection,
        'sc' => $schoolCampus,
        'cn' => $companyName,
        'an' => $adviserName,
        'ae' => $adviserEmail,
        'td' => $targetDate,
        'rh' => $requiredHours,
        'id' => $current['id'],
    ]);

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

