<?php
declare(strict_types=1);

require_once __DIR__ . '/../../common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$body = read_json_body();
$employeeId = trim((string)($body['employee_id'] ?? ''));
$fullName = trim((string)($body['full_name'] ?? ''));
$email = trim((string)($body['email'] ?? ''));
$password = trim((string)($body['password'] ?? ''));
$department = trim((string)($body['department'] ?? 'College of Information and Communications Technology'));
$role = trim((string)($body['role'] ?? 'admin'));
$assignedSection = trim((string)($body['assigned_section'] ?? 'BSIT 4-A'));
$contactNumber = trim((string)($body['contact_number'] ?? ''));

if ($employeeId === '' || $fullName === '' || $email === '' || $password === '') {
    respond(400, ['success' => false, 'message' => 'Employee ID, full name, email, and password are required.']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(400, ['success' => false, 'message' => 'Invalid email address format.']);
}

$validRoles = ['admin', 'coordinator', 'adviser'];
if (!in_array($role, $validRoles, true)) {
    $role = 'admin';
}

try {
    $pdo = get_db_connection();

    // Check duplicate
    $check = $pdo->prepare('SELECT id FROM professors WHERE employee_id = :emp OR email = :email LIMIT 1');
    $check->execute(['emp' => $employeeId, 'email' => $email]);
    if ($check->fetch()) {
        respond(409, ['success' => false, 'message' => 'A professor account with this Employee ID or Email already exists.']);
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare('INSERT INTO professors (employee_id, full_name, email, password, department, role, assigned_section, contact_number) VALUES (:emp, :name, :email, :pass, :dept, :role, :sec, :contact)');
    $stmt->execute([
        'emp'     => $employeeId,
        'name'    => $fullName,
        'email'   => $email,
        'pass'    => $hashedPassword,
        'dept'    => $department,
        'role'    => $role,
        'sec'     => $assignedSection,
        'contact' => $contactNumber ?: null,
    ]);

    $newId = (int)$pdo->lastInsertId();

    $fetch = $pdo->prepare('SELECT * FROM professors WHERE id = :id LIMIT 1');
    $fetch->execute(['id' => $newId]);
    $newProf = $fetch->fetch();

    respond(201, [
        'success'   => true,
        'message'   => 'Professor / Admin account created successfully.',
        'professor' => format_professor_user($newProf),
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

