<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/config.php';

// CORS Headers
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . ($origin !== '' ? $origin : '*'));
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Bypass-Tunnel-Reminder, bypass-tunnel-reminder, *');
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
        'school_campus'   => $row['school_campus'] ?? 'NEUST - Sumacab Campus',
        'company_name'    => $row['company_name'] ?? 'Assigned Agency',
        'company_address' => $row['company_address'] ?? null,
        'trainer_name'    => $row['trainer_name'] ?? null,
        'adviser_name'    => $row['adviser_name'] ?? 'OJT Coordinator',
        'adviser_email'   => $row['adviser_email'] ?? 'adviser@neust.edu.ph',
        'start_date'      => $row['start_date'] ?? null,
        'target_date'     => $row['target_date'] ?? '2026-12-15',
        'required_hours'  => (int)($row['required_hours'] ?? 0),
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
        'campus'           => $row['campus'] ?? 'NEUST - Sumacab Campus',
        'role'             => $row['role'] ?? 'admin',
        'assigned_section' => $row['assigned_section'] ?? 'BSIT 4-A',
        'contact_number'   => $row['contact_number'] ?? null,
        'avatar_url'       => $row['avatar_url'] ?? null,
    ];
}

function resolve_college_and_code(string $course, string $college = ''): array {
    $c = strtoupper($course);
    $col = trim($college);
    $code = 'BSIT';

    if (str_contains($c, 'CIVIL ENGINEERING')) {
        $code = 'BSCE';
        $col = $col !== '' ? $col : 'College of Engineering';
    } elseif (str_contains($c, 'ELECTRICAL ENGINEERING')) {
        $code = 'BSEE';
        $col = $col !== '' ? $col : 'College of Engineering';
    } elseif (str_contains($c, 'MECHANICAL ENGINEERING')) {
        $code = 'BSME';
        $col = $col !== '' ? $col : 'College of Engineering';
    } elseif (str_contains($c, 'ENGINEERING')) {
        $code = 'BSCE';
        $col = $col !== '' ? $col : 'College of Engineering';
    } elseif (str_contains($c, 'ARCHITECTURE') || str_contains($c, 'DRAFTING') || str_contains($c, 'CADD') || str_contains($c, 'BUILDING')) {
        $code = 'BSARCH';
        $col = $col !== '' ? $col : 'College of Architecture';
    } elseif (str_contains($c, 'ELEMENTARY EDUCATION') || str_contains($c, 'BEED')) {
        $code = 'BEED';
        $col = $col !== '' ? $col : 'College of Education';
    } elseif (str_contains($c, 'SECONDARY EDUCATION') || str_contains($c, 'BSED')) {
        $code = 'BSED';
        $col = $col !== '' ? $col : 'College of Education';
    } elseif (str_contains($c, 'EDUCATION') || str_contains($c, 'TEACHER') || str_contains($c, 'PHYSICAL EDUCATION')) {
        $code = 'BSED';
        $col = $col !== '' ? $col : 'College of Education';
    } elseif (str_contains($c, 'CRIMINOLOGY')) {
        $code = 'BSCRIM';
        $col = $col !== '' ? $col : 'College of Criminology';
    } elseif (str_contains($c, 'BUSINESS ADMINISTRATION') || str_contains($c, 'BSBA') || str_contains($c, 'ENTREPRENEURSHIP')) {
        $code = 'BSBA';
        $col = $col !== '' ? $col : 'College of Management and Business Technology';
    } elseif (str_contains($c, 'HOSPITALITY')) {
        $code = 'BSHM';
        $col = $col !== '' ? $col : 'College of Management and Business Technology';
    } elseif (str_contains($c, 'TOURISM')) {
        $code = 'BSTM';
        $col = $col !== '' ? $col : 'College of Management and Business Technology';
    } elseif (str_contains($c, 'PUBLIC ADMINISTRATION')) {
        $code = 'BPA';
        $col = $col !== '' ? $col : 'College of Public Administration and Disaster Management';
    } elseif (str_contains($c, 'DATA SCIENCE') || str_contains($c, 'BSDS')) {
        $code = 'BSDS';
        $col = $col !== '' ? $col : 'College of Information and Communications Technology';
    } else {
        $code = 'BSIT';
        $col = $col !== '' ? $col : 'College of Information and Communications Technology';
    }

    return [$col, $code];
}

function compute_canonical_section(string $course, string $section, $yearLevel = 4): string {
    [$col, $code] = resolve_college_and_code($course);
    $trimmed = trim($section);
    if ($trimmed === '') {
        return "{$code} {$yearLevel}-A";
    }

    $upper = strtoupper($trimmed);
    // If user already typed "BSCE 4-A" or "BSIT 4-B", keep it
    if (str_starts_with($upper, 'BS') || str_starts_with($upper, 'BE') || str_starts_with($upper, 'BP')) {
        return $upper;
    }

    // Extract section letter / number (e.g. "4-A" -> "A", "A" -> "A", "SECTION A" -> "A")
    preg_match('/[A-Z0-9]+$/', $upper, $matches);
    $secLetter = !empty($matches) ? $matches[0] : 'A';

    return "{$code} {$yearLevel}-{$secLetter}";
}

function query_int(string $key): ?int {
    $raw = $_GET[$key] ?? null;
    $val = filter_var($raw, FILTER_VALIDATE_INT);
    if ($val === false && $raw !== null && is_numeric($raw)) {
        $val = (int)$raw;
    }
    return ($val !== false && (int)$val > 0) ? (int)$val : null;
}

function sanitize_upload_filename(?string $name): string {
    if ($name === null || $name === '') {
        return '';
    }
    return (string)preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $name);
}

/**
 * Decodes and saves a base64 data URI under backend/uploads/<subDir>/.
 * Returns ['url' => 'uploads/<subDir>/<file>', 'name' => <displayName>] or null if not a valid base64 data URI.
 */
function save_base64_upload(
    string $dataUri,
    string $subDir,
    string $filePrefix,
    string $origName = '',
    string $noMimeExt = 'bin',
    bool $withRand = true
): ?array {
    $cleanOrig = sanitize_upload_filename($origName);
    if (!str_starts_with($dataUri, 'data:')) {
        return null;
    }

    preg_match('/^data:([^;]+);base64,/', $dataUri, $matches);
    $mime = $matches[1] ?? '';
    $parts = explode(',', $dataUri, 2);
    $fileData = base64_decode($parts[1] ?? '', true);
    if ($fileData === false) {
        $fileData = base64_decode($parts[1] ?? '');
    }
    if ($fileData === false) {
        return null;
    }

    $uploadsDir = dirname(__DIR__) . '/uploads/' . $subDir;
    if (!is_dir($uploadsDir)) {
        @mkdir($uploadsDir, 0777, true);
    }

    $ext = $noMimeExt;
    $origExt = $cleanOrig !== '' ? pathinfo($cleanOrig, PATHINFO_EXTENSION) : '';
    if ($origExt !== '') {
        $ext = preg_replace('/[^a-z0-9]/', '', strtolower($origExt)) ?: 'bin';
    } elseif ($mime !== '') {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/jpg'  => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
            'text/plain' => 'txt',
        ];
        $ext = $mimeMap[$mime] ?? 'bin';
    }

    $stamp = $withRand ? (time() . '_' . mt_rand(100, 999)) : (string)time();
    $fileName = $filePrefix . $stamp . '.' . $ext;
    file_put_contents($uploadsDir . '/' . $fileName, $fileData);

    return [
        'url'  => 'uploads/' . $subDir . '/' . $fileName,
        'name' => $cleanOrig !== '' ? $cleanOrig : $fileName,
    ];
}

