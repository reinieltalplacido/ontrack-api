<?php
// backend/api/auth/register.php
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$student_id     = trim($input['student_id'] ?? '');
$full_name      = trim($input['full_name'] ?? '');
$email          = trim($input['email'] ?? '');
$password       = trim($input['password'] ?? '');
$course_section = trim($input['course_section'] ?? 'BSIT 4-A');
$school_campus  = trim($input['school_campus'] ?? 'NEUST - Sumacab Campus');
$company_name   = trim($input['company_name'] ?? 'Assigned Partner Agency');
$adviser_name   = trim($input['adviser_name'] ?? 'OJT Coordinator');
$adviser_email  = trim($input['adviser_email'] ?? '');
$start_date     = !empty($input['start_date']) ? $input['start_date'] : date('Y-m-d');
$target_date    = !empty($input['target_date']) ? $input['target_date'] : date('Y-m-d', strtotime('+4 months'));
$required_hours = intval($input['required_hours'] ?? 486);

if (empty($student_id) || empty($full_name) || empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Student ID, Full Name, Email, and Password are required.']);
    exit();
}

try {
    // Check for duplicate email or student_id
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR student_id = :sid LIMIT 1");
    $checkStmt->execute(['email' => $email, 'sid' => $student_id]);
    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'An account with this Email or Student ID already exists.']);
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    $insertSql = "INSERT INTO users (
        student_id, full_name, email, password, course_section, school_campus, 
        company_name, adviser_name, adviser_email, start_date, target_date, required_hours
    ) VALUES (
        :student_id, :full_name, :email, :password, :course_section, :school_campus,
        :company_name, :adviser_name, :adviser_email, :start_date, :target_date, :required_hours
    )";

    $stmt = $pdo->prepare($insertSql);
    $stmt->execute([
        'student_id'     => $student_id,
        'full_name'      => $full_name,
        'email'          => $email,
        'password'       => $hashed_password,
        'course_section' => $course_section,
        'school_campus'  => $school_campus,
        'company_name'   => $company_name,
        'adviser_name'   => $adviser_name,
        'adviser_email'  => $adviser_email,
        'start_date'     => $start_date,
        'target_date'    => $target_date,
        'required_hours' => $required_hours
    ]);

    $newId = $pdo->lastInsertId();

    $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $userStmt->execute(['id' => $newId]);
    $newUser = $userStmt->fetch();
    unset($newUser['password']);
    $newUser['rendered_hours'] = 0;

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful!',
        'user'    => $newUser
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
