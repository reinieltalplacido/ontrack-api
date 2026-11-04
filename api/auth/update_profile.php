<?php
// backend/api/auth/update_profile.php
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$user_id        = intval($input['user_id'] ?? 0);
$full_name      = trim($input['full_name'] ?? '');
$course_section = trim($input['course_section'] ?? '');
$school_campus  = trim($input['school_campus'] ?? '');
$company_name   = trim($input['company_name'] ?? '');
$adviser_name   = trim($input['adviser_name'] ?? '');
$target_date    = !empty($input['target_date']) ? $input['target_date'] : null;
$required_hours = isset($input['required_hours']) ? intval($input['required_hours']) : null;
$new_password   = trim($input['new_password'] ?? '');

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid user_id is required.']);
    exit();
}

try {
    // Check if user exists
    $checkStmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $checkStmt->execute(['id' => $user_id]);
    $user = $checkStmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit();
    }

    $updates = [];
    $params = ['id' => $user_id];

    if (!empty($full_name)) {
        $updates[] = "full_name = :full_name";
        $params['full_name'] = $full_name;
    }
    if (!empty($course_section)) {
        $updates[] = "course_section = :course_section";
        $params['course_section'] = $course_section;
    }
    if (!empty($school_campus)) {
        $updates[] = "school_campus = :school_campus";
        $params['school_campus'] = $school_campus;
    }
    if (!empty($company_name)) {
        $updates[] = "company_name = :company_name";
        $params['company_name'] = $company_name;
    }
    if (!empty($adviser_name)) {
        $updates[] = "adviser_name = :adviser_name";
        $params['adviser_name'] = $adviser_name;
    }
    if (!empty($target_date)) {
        $updates[] = "target_date = :target_date";
        $params['target_date'] = $target_date;
    }
    if ($required_hours !== null && $required_hours > 0) {
        $updates[] = "required_hours = :required_hours";
        $params['required_hours'] = $required_hours;
    }
    if (!empty($new_password)) {
        $updates[] = "password = :password";
        $params['password'] = password_hash($new_password, PASSWORD_BCRYPT);
    }

    if (!empty($updates)) {
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    // Return updated user profile
    $fetchStmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $fetchStmt->execute(['id' => $user_id]);
    $updatedUser = $fetchStmt->fetch();
    unset($updatedUser['password']);

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully!',
        'user'    => $updatedUser
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

