<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Development CORS allow-list
$allowedOrigins = [
    'http://localhost',
    'http://localhost:8100',
    'http://127.0.0.1:8100',
    'http://localhost:4200',
    'https://localhost',
    'capacitor://localhost',
    'ionic://localhost',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . ($origin !== '' ? $origin : '*'));
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        respond(400, ['success' => false, 'message' => 'Request body is required.']);
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        respond(400, ['success' => false, 'message' => 'Invalid JSON request body.']);
    }

    return $decoded;
}

function validate_student(array $input): array
{
    $studentNo = trim((string)($input['student_no'] ?? ''));
    $fullName = trim((string)($input['full_name'] ?? ''));
    $course = trim((string)($input['course'] ?? ''));
    $yearLevel = filter_var($input['year_level'] ?? null, FILTER_VALIDATE_INT);

    $companyName   = trim((string)($input['company_name'] ?? 'Assigned Agency'));
    $adviserName   = trim((string)($input['adviser_name'] ?? 'OJT Coordinator'));
    
    // Secure Password Hashing (BCrypt)
    $rawPassword   = trim((string)($input['password'] ?? 'Password123!'));
    $hashedPassword = password_hash($rawPassword, PASSWORD_BCRYPT);

    $requiredHours = intval($input['required_hours'] ?? 486);
    $targetDate    = !empty($input['target_date']) ? $input['target_date'] : '2026-12-15';

    $errors = [];
    if ($studentNo === '') $errors['student_no'] = 'Student number is required.';
    if ($fullName === '') $errors['full_name'] = 'Full name is required.';
    if ($course === '') $errors['course'] = 'Course is required.';
    if ($yearLevel === false || $yearLevel === null || $yearLevel < 1 || $yearLevel > 6) {
        $errors['year_level'] = 'Year level must be an integer from 1 to 6.';
    }

    if ($errors) {
        respond(422, [
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $errors,
        ]);
    }

    return [
        'student_no'     => $studentNo,
        'full_name'      => $fullName,
        'course'         => $course,
        'year_level'     => $yearLevel,
        'company_name'   => $companyName,
        'adviser_name'   => $adviserName,
        'password'       => $hashedPassword,
        'required_hours' => $requiredHours,
        'target_date'    => $targetDate,
    ];
}

try {
    $pdo = get_db_connection();
    $method = $_SERVER['REQUEST_METHOD'];
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (isset($_GET['id']) && ($id === false || $id === null || $id < 1)) {
        respond(400, ['success' => false, 'message' => 'The id parameter must be a positive integer.']);
    }

    switch ($method) {
        case 'GET':
            if ($id !== null && $id !== false) {
                $stmt = $pdo->prepare(
                    'SELECT id, student_no, full_name, course, year_level, company_name, adviser_name, required_hours, target_date, created_at, updated_at
                     FROM students WHERE id = :id'
                );
                $stmt->execute(['id' => $id]);
                $student = $stmt->fetch();

                if (!$student) {
                    respond(404, ['success' => false, 'message' => 'Student not found.']);
                }

                $student['id'] = (int)$student['id'];
                $student['year_level'] = (int)$student['year_level'];
                respond(200, ['success' => true, 'data' => $student]);
            }

            $stmt = $pdo->query(
                'SELECT id, student_no, full_name, course, year_level, company_name, adviser_name, required_hours, target_date, created_at, updated_at
                 FROM students ORDER BY id DESC'
            );
            $students = $stmt->fetchAll();

            foreach ($students as &$student) {
                $student['id'] = (int)$student['id'];
                $student['year_level'] = (int)$student['year_level'];
            }
            unset($student);

            respond(200, ['success' => true, 'data' => $students]);

        case 'POST':
            $body = read_json_body();

            // Handle Login Verification Action
            if (isset($body['action']) && $body['action'] === 'login') {
                $identifier = trim((string)($body['identifier'] ?? ($body['student_no'] ?? '')));
                $password = trim((string)($body['password'] ?? ''));

                if ($identifier === '' || $password === '') {
                    respond(400, ['success' => false, 'message' => 'Student ID and password are required.']);
                }

                $stmt = $pdo->prepare('SELECT * FROM students WHERE student_no = :sno LIMIT 1');
                $stmt->execute(['sno' => $identifier]);
                $user = $stmt->fetch();

                if (!$user) {
                    respond(401, ['success' => false, 'message' => 'Invalid student credentials.']);
                }

                $passwordMatches = password_verify($password, $user['password']) || ($password === $user['password']);
                if (!$passwordMatches) {
                    respond(401, ['success' => false, 'message' => 'Invalid student credentials.']);
                }

                // Omit password hash from response
                unset($user['password']);
                respond(200, [
                    'success' => true,
                    'message' => 'Login successful',
                    'user'    => $user
                ]);
            }

            // Normal Student Registration (with hashed password)
            $student = validate_student($body);
            $stmt = $pdo->prepare(
                'INSERT INTO students (student_no, full_name, course, year_level, company_name, adviser_name, password, required_hours, target_date)
                 VALUES (:student_no, :full_name, :course, :year_level, :company_name, :adviser_name, :password, :required_hours, :target_date)'
            );
            $stmt->execute($student);

            $newId = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare(
                'SELECT id, student_no, full_name, course, year_level, company_name, adviser_name, required_hours, target_date, created_at, updated_at
                 FROM students WHERE id = :id'
            );
            $stmt->execute(['id' => $newId]);
            $created = $stmt->fetch();
            $created['id'] = (int)$created['id'];
            $created['year_level'] = (int)$created['year_level'];

            respond(201, ['success' => true, 'message' => 'Student created.', 'data' => $created]);

        case 'PUT':
            if ($id === null || $id === false) {
                respond(400, ['success' => false, 'message' => 'The id query parameter is required for update.']);
            }

            $student = validate_student(read_json_body());
            $stmt = $pdo->prepare(
                'UPDATE students
                 SET student_no = :student_no, full_name = :full_name, course = :course, year_level = :year_level, company_name = :company_name, adviser_name = :adviser_name, password = :password, required_hours = :required_hours, target_date = :target_date
                 WHERE id = :id'
            );
            $stmt->execute(array_merge($student, ['id' => $id]));

            if ($stmt->rowCount() === 0) {
                $check = $pdo->prepare('SELECT id FROM students WHERE id = :id');
                $check->execute(['id' => $id]);
                if (!$check->fetch()) {
                    respond(404, ['success' => false, 'message' => 'Student not found.']);
                }
            }

            $stmt = $pdo->prepare(
                'SELECT id, student_no, full_name, course, year_level, company_name, adviser_name, required_hours, target_date, created_at, updated_at
                 FROM students WHERE id = :id'
            );
            $stmt->execute(['id' => $id]);
            $updated = $stmt->fetch();
            $updated['id'] = (int)$updated['id'];
            $updated['year_level'] = (int)$updated['year_level'];

            respond(200, ['success' => true, 'message' => 'Student updated.', 'data' => $updated]);

        case 'DELETE':
            if ($id === null || $id === false) {
                respond(400, ['success' => false, 'message' => 'The id query parameter is required for deletion.']);
            }

            $stmt = $pdo->prepare('DELETE FROM students WHERE id = :id');
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() === 0) {
                respond(404, ['success' => false, 'message' => 'Student not found.']);
            }

            respond(200, ['success' => true, 'message' => 'Student deleted.']);

        default:
            respond(405, ['success' => false, 'message' => 'Method not allowed.']);
    }
} catch (PDOException $e) {
    if ((int)$e->errorInfo[1] === 1062) {
        respond(409, ['success' => false, 'message' => 'A student with that student number already exists.']);
    }

    respond(500, ['success' => false, 'message' => 'Internal server error: ' . $e->getMessage()]);
}
