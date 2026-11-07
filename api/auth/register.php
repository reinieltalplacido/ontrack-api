<?php
declare(strict_types=1);

require_once __DIR__ . '/../common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$body = read_json_body();
$studentId     = trim((string)($body['student_id'] ?? ($body['student_no'] ?? '')));
$fullName      = trim((string)($body['full_name'] ?? ''));
$email         = trim((string)($body['email'] ?? ''));
$password      = trim((string)($body['password'] ?? ''));
$course        = trim((string)($body['course'] ?? 'BSIT'));
$yearLevel     = trim((string)($body['year_level'] ?? '4'));
$section       = trim((string)($body['section'] ?? 'A'));

// Compute canonical course_section (e.g. BSIT 4-A, BSIT 4-B, etc.)
if (!empty($body['course_section'])) {
    $courseSection = trim((string)$body['course_section']);
} elseif ($section !== '') {
    if (str_contains($section, '-')) {
        $courseSection = $section;
    } else {
        $courseSection = "{$course} {$yearLevel}-{$section}";
    }
} else {
    $courseSection = "{$course} 4-A";
}

$schoolCampus  = trim((string)($body['school_campus'] ?? 'NEUST - Sumacab Campus'));
$companyName   = trim((string)($body['company_name'] ?? 'Assigned Agency'));
$adviserName   = trim((string)($body['adviser_name'] ?? 'OJT Coordinator'));
$adviserEmail  = trim((string)($body['adviser_email'] ?? 'adviser@neust.edu.ph'));
$targetDate    = !empty($body['target_date']) ? $body['target_date'] : '2026-12-15';
$requiredHours = intval($body['required_hours'] ?? 486);

if ($studentId === '' || $fullName === '' || $password === '') {
    respond(422, ['success' => false, 'message' => 'Student ID, full name, and password are required.']);
}

try {
    $pdo = get_db_connection();

    // Check if student_no or email exists
    $check = $pdo->prepare('SELECT id, student_no, email FROM students WHERE student_no = :sid OR (email = :em AND email != \'\') LIMIT 1');
    $check->execute(['sid' => $studentId, 'em' => $email]);
    $existing = $check->fetch();
    if ($existing) {
        if ($existing['student_no'] === $studentId) {
            respond(409, ['success' => false, 'message' => "Student ID '{$studentId}' is already registered. Please sign in or use a different ID."]);
        } else {
            respond(409, ['success' => false, 'message' => "Email '{$email}' is already registered. Please sign in or use a different email."]);
        }
    }

    // Auto-fetch and bind assigned professor / coordinator for this section
    $secLetter = str_contains($section, '-') ? explode('-', $section)[1] : $section;
    $secEnd = '%-' . strtoupper($secLetter);
    $coordStmt = $pdo->prepare('SELECT id, full_name, email FROM professors WHERE assigned_section = :sec OR assigned_section LIKE :secEnd ORDER BY id ASC LIMIT 1');
    $coordStmt->execute(['sec' => $courseSection, 'secEnd' => $secEnd]);
    $coord = $coordStmt->fetch();

    $professorId = null;
    if ($coord) {
        $professorId = (int)$coord['id'];
        $adviserName = $coord['full_name'];
        $adviserEmail = $coord['email'];
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        'INSERT INTO students (professor_id, student_no, full_name, email, password, course_section, school_campus, company_name, adviser_name, adviser_email, target_date, required_hours)
         VALUES (:pid, :sid, :fn, :em, :pwd, :cs, :sc, :cn, :an, :ae, :td, :rh)'
    );
    $stmt->execute([
        'pid' => $professorId,
        'sid' => $studentId,
        'fn'  => $fullName,
        'em'  => $email !== '' ? $email : null,
        'pwd' => $hashedPassword,
        'cs'  => $courseSection,
        'sc'  => $schoolCampus,
        'cn'  => $companyName,
        'an'  => $adviserName,
        'ae'  => $adviserEmail,
        'td'  => $targetDate,
        'rh'  => $requiredHours,
    ]);

    $newId = (int)$pdo->lastInsertId();
    $getStmt = $pdo->prepare('SELECT * FROM students WHERE id = :id');
    $getStmt->execute(['id' => $newId]);
    $created = $getStmt->fetch();

    respond(201, [
        'success' => true,
        'message' => 'Registration successful! You may now log in.',
        'user'    => format_student_user($created),
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
