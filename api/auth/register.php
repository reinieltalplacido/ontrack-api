<?php
declare(strict_types=1);

require_once __DIR__ . '/../common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$body = read_json_body();
$studentId     = trim((string)($body['student_id'] ?? ($body['student_no'] ?? '')));
$fullName      = strtoupper(trim((string)($body['full_name'] ?? '')));
$email         = trim((string)($body['email'] ?? ''));
$password      = trim((string)($body['password'] ?? ''));
$course        = trim((string)($body['course'] ?? 'BSIT'));
$yearLevel     = trim((string)($body['year_level'] ?? '4'));
$section       = trim((string)($body['section'] ?? 'A'));

// Compute canonical course_section (e.g. BSCE 4-A, BSIT 4-A, etc.)
$courseSection = compute_canonical_section($course, $section, $yearLevel);
[$resolvedDept, $courseCode] = resolve_college_and_code($course);

$schoolCampus   = trim((string)($body['school_campus'] ?? 'NEUST - Sumacab Campus'));
$companyName    = trim((string)($body['company_name'] ?? 'Assigned Agency'));
$companyAddress = trim((string)($body['company_address'] ?? ''));
$trainerName    = trim((string)($body['trainer_name'] ?? ''));
$adviserName    = trim((string)($body['adviser_name'] ?? 'OJT Coordinator'));
$adviserEmail   = trim((string)($body['adviser_email'] ?? 'adviser@neust.edu.ph'));
$startDate      = !empty($body['start_date']) ? $body['start_date'] : null;
$targetDate     = !empty($body['target_date']) ? $body['target_date'] : '2026-12-15';
$requiredHours  = intval($body['required_hours'] ?? 0);

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

    // Auto-fetch and bind assigned professor / coordinator for this campus, course, college, and section
    preg_match('/[A-Z0-9]+$/', strtoupper(trim($section)), $matches);
    $secLetter = !empty($matches) ? $matches[0] : 'A';
    $secEnd = '%-' . $secLetter;
    $courseSecPattern = $courseCode . ' %-' . $secLetter;

    $coord = null;

    // Campus-aware lookups
    if ($schoolCampus !== '') {
        $coordStmt = $pdo->prepare('SELECT id, full_name, email, campus FROM professors WHERE campus = :campus AND assigned_section = :sec LIMIT 1');
        $coordStmt->execute(['campus' => $schoolCampus, 'sec' => $courseSection]);
        $coord = $coordStmt->fetch();

        if (!$coord) {
            $coordStmt = $pdo->prepare('SELECT id, full_name, email, campus FROM professors WHERE campus = :campus AND assigned_section LIKE :courseSecPattern ORDER BY id ASC LIMIT 1');
            $coordStmt->execute(['campus' => $schoolCampus, 'courseSecPattern' => $courseSecPattern]);
            $coord = $coordStmt->fetch();
        }

        if (!$coord && $resolvedDept !== '') {
            $coordStmt = $pdo->prepare('SELECT id, full_name, email, campus FROM professors WHERE campus = :campus AND department = :dept AND assigned_section LIKE :secEnd ORDER BY id ASC LIMIT 1');
            $coordStmt->execute(['campus' => $schoolCampus, 'dept' => $resolvedDept, 'secEnd' => $secEnd]);
            $coord = $coordStmt->fetch();
        }

        if (!$coord && $resolvedDept !== '') {
            $coordStmt = $pdo->prepare('SELECT id, full_name, email, campus FROM professors WHERE campus = :campus AND department = :dept ORDER BY id ASC LIMIT 1');
            $coordStmt->execute(['campus' => $schoolCampus, 'dept' => $resolvedDept]);
            $coord = $coordStmt->fetch();
        }
    }

    // Cross-campus fallback lookups
    if (!$coord) {
        $coordStmt = $pdo->prepare('SELECT id, full_name, email, campus FROM professors WHERE assigned_section = :sec ORDER BY id ASC LIMIT 1');
        $coordStmt->execute(['sec' => $courseSection]);
        $coord = $coordStmt->fetch();
    }

    if (!$coord) {
        $coordStmt = $pdo->prepare('SELECT id, full_name, email, campus FROM professors WHERE assigned_section LIKE :courseSecPattern ORDER BY id ASC LIMIT 1');
        $coordStmt->execute(['courseSecPattern' => $courseSecPattern]);
        $coord = $coordStmt->fetch();
    }

    if (!$coord && $resolvedDept !== '') {
        $coordStmt = $pdo->prepare('SELECT id, full_name, email, campus FROM professors WHERE department = :dept AND assigned_section LIKE :secEnd ORDER BY id ASC LIMIT 1');
        $coordStmt->execute(['dept' => $resolvedDept, 'secEnd' => $secEnd]);
        $coord = $coordStmt->fetch();
    }

    if (!$coord && $resolvedDept !== '') {
        $coordStmt = $pdo->prepare('SELECT id, full_name, email, campus FROM professors WHERE department = :dept ORDER BY id ASC LIMIT 1');
        $coordStmt->execute(['dept' => $resolvedDept]);
        $coord = $coordStmt->fetch();
    }

    if (!$coord) {
        $coordStmt = $pdo->query('SELECT id, full_name, email, campus FROM professors ORDER BY id ASC LIMIT 1');
        $coord = $coordStmt->fetch();
    }

    $professorId = null;
    if ($coord) {
        $professorId = (int)$coord['id'];
        $adviserName = $coord['full_name'];
        $adviserEmail = $coord['email'];
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        'INSERT INTO students (professor_id, student_no, full_name, email, password, course, year_level, course_section, school_campus, company_name, company_address, trainer_name, adviser_name, adviser_email, start_date, target_date, required_hours)
         VALUES (:pid, :sid, :fn, :em, :pwd, :crs, :yl, :cs, :sc, :cn, :ca, :tn, :an, :ae, :sd, :td, :rh)'
    );
    $stmt->execute([
        'pid' => $professorId,
        'sid' => $studentId,
        'fn'  => $fullName,
        'em'  => $email !== '' ? $email : null,
        'pwd' => $hashedPassword,
        'crs' => $course,
        'yl'  => (int)$yearLevel,
        'cs'  => $courseSection,
        'sc'  => $schoolCampus,
        'cn'  => $companyName,
        'ca'  => $companyAddress !== '' ? $companyAddress : null,
        'tn'  => $trainerName !== '' ? $trainerName : null,
        'an'  => $adviserName,
        'ae'  => $adviserEmail,
        'sd'  => $startDate,
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
