<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/config.php';

// CORS Headers
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . ($origin !== '' ? $origin : '*'));
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function respond(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function format_student_user(array $row): array {
    return [
        'id'             => (int)$row['id'],
        'student_id'     => $row['student_no'] ?? '',
        'full_name'      => $row['full_name'] ?? '',
        'email'          => $row['email'] ?? ($row['student_no'] ?? '') . '@student.neust.edu.ph',
        'course_section' => $row['course_section'] ?? ($row['course'] ?? 'BSIT 4-A'),
        'school_campus'  => $row['school_campus'] ?? 'NEUST - Sumacab Campus',
        'company_name'   => $row['company_name'] ?? 'Assigned Agency',
        'adviser_name'   => $row['adviser_name'] ?? 'OJT Coordinator',
        'adviser_email'  => $row['adviser_email'] ?? 'adviser@neust.edu.ph',
        'target_date'    => $row['target_date'] ?? '2026-12-15',
        'required_hours' => (int)($row['required_hours'] ?? 486),
        'professor_id'   => isset($row['professor_id']) ? (int)$row['professor_id'] : null,
    ];
}

function format_professor_user(array $row): array {
    return [
        'id'               => (int)$row['id'],
        'employee_id'      => $row['employee_id'] ?? '',
        'full_name'        => $row['full_name'] ?? '',
        'email'            => $row['email'] ?? '',
        'department'       => $row['department'] ?? 'College of Information and Communications Technology',
        'role'             => $row['role'] ?? 'admin',
        'assigned_section' => $row['assigned_section'] ?? 'BSIT 4-A',
        'contact_number'   => $row['contact_number'] ?? null,
        'avatar_url'       => $row['avatar_url'] ?? null,
    ];
}


