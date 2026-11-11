<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    respond(405, ['success' => false, 'message' => 'Method not allowed']);
}

$idParam     = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$studentNo   = trim((string)($_GET['student_no'] ?? ''));
$section     = trim((string)($_GET['section'] ?? ''));
$professorId = filter_var($_GET['professor_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$search      = trim((string)($_GET['search'] ?? ''));

$baseSelect = 'SELECT
    s.id,
    s.professor_id,
    s.student_no,
    s.full_name,
    s.email,
    s.course,
    s.course_section,
    s.year_level,
    s.school_campus,
    s.company_name,
    s.company_address,
    s.trainer_name,
    COALESCE(p.full_name, s.adviser_name) AS adviser_name,
    COALESCE(p.email, s.adviser_email) AS adviser_email,
    s.required_hours,
    s.start_date,
    s.target_date,
    s.created_at
FROM students s
LEFT JOIN professors p ON s.professor_id = p.id';

/**
 * Shape a student row for JSON responses without exposing credentials.
 */
function build_student_payload(array $row): array
{
    $formatted = format_student_user($row);
    $formatted['student_no'] = $row['student_no'] ?? '';
    $formatted['course']     = $row['course'] ?? 'BSIT';
    $formatted['year_level'] = (int)($row['year_level'] ?? 4);
    $formatted['created_at'] = $row['created_at'] ?? null;

    return $formatted;
}

try {
    $pdo = get_db_connection();

    if ($idParam || $studentNo !== '') {
        if ($idParam) {
            $stmt = $pdo->prepare($baseSelect . ' WHERE s.id = :id LIMIT 1');
            $stmt->execute(['id' => $idParam]);
        } else {
            $stmt = $pdo->prepare($baseSelect . ' WHERE s.student_no = :student_no LIMIT 1');
            $stmt->execute(['student_no' => $studentNo]);
        }

        $row = $stmt->fetch();
        if (!$row) {
            respond(404, [
                'success' => false,
                'found'   => false,
                'message' => 'Student not found',
            ]);
        }

        respond(200, [
            'success' => true,
            'found'   => true,
            'student' => build_student_payload($row),
        ]);
    }

    $query  = $baseSelect . ' WHERE 1=1';
    $params = [];

    if ($professorId) {
        $query .= ' AND s.professor_id = :professor_id';
        $params['professor_id'] = $professorId;
    }

    if ($section !== '') {
        $query .= ' AND s.course_section = :section';
        $params['section'] = $section;
    }

    if ($search !== '') {
        $query .= ' AND (s.full_name LIKE :search1 OR s.student_no LIKE :search2 OR s.company_name LIKE :search3)';
        $like = "%{$search}%";
        $params['search1'] = $like;
        $params['search2'] = $like;
        $params['search3'] = $like;
    }

    $query .= ' ORDER BY s.id ASC';

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $students = array_map('build_student_payload', $rows);

    respond(200, [
        'success'  => true,
        'count'    => count($students),
        'students' => $students,
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error while fetching students.']);
}
