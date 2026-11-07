<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

$section = trim((string)($_GET['section'] ?? ''));

try {
    $pdo = get_db_connection();

    if ($section !== '') {
        // Match exact section or section ending (e.g. BSIT 4-B or %-B)
        $cleanSec = strtoupper(trim($section));
        $secEnd = '%-' . (str_contains($cleanSec, '-') ? explode('-', $cleanSec)[1] : $cleanSec);
        $stmt = $pdo->prepare(
            'SELECT id, employee_id, full_name, email, department, role, assigned_section, contact_number 
             FROM professors 
             WHERE assigned_section = :sec OR assigned_section LIKE :secEnd 
             ORDER BY id ASC 
             LIMIT 1'
        );
        $stmt->execute(['sec' => $cleanSec, 'secEnd' => $secEnd]);
        $coordinator = $stmt->fetch();

        if ($coordinator) {
            respond(200, [
                'success'     => true,
                'found'       => true,
                'coordinator' => [
                    'id'               => (int)$coordinator['id'],
                    'employee_id'      => $coordinator['employee_id'],
                    'full_name'        => $coordinator['full_name'],
                    'email'            => $coordinator['email'],
                    'department'       => $coordinator['department'],
                    'role'             => $coordinator['role'],
                    'assigned_section' => $coordinator['assigned_section'],
                    'contact_number'   => $coordinator['contact_number'],
                ],
            ]);
        } else {
            // Fallback to default lead admin coordinator
            $fallbackStmt = $pdo->prepare('SELECT id, employee_id, full_name, email, department, role, assigned_section, contact_number FROM professors ORDER BY id ASC LIMIT 1');
            $fallbackStmt->execute();
            $defaultCoord = $fallbackStmt->fetch();

            respond(200, [
                'success'     => true,
                'found'       => false,
                'coordinator' => $defaultCoord ? [
                    'id'               => (int)$defaultCoord['id'],
                    'employee_id'      => $defaultCoord['employee_id'],
                    'full_name'        => $defaultCoord['full_name'],
                    'email'            => $defaultCoord['email'],
                    'department'       => $defaultCoord['department'],
                    'role'             => $defaultCoord['role'],
                    'assigned_section' => $section,
                    'contact_number'   => $defaultCoord['contact_number'],
                ] : null,
            ]);
        }
    }

    // No section specified -> return all section-coordinator mappings
    $stmt = $pdo->query('SELECT id, employee_id, full_name, email, department, role, assigned_section, contact_number FROM professors ORDER BY assigned_section ASC');
    $all = $stmt->fetchAll();

    $coordinators = array_map(function ($row) {
        return [
            'id'               => (int)$row['id'],
            'employee_id'      => $row['employee_id'],
            'full_name'        => $row['full_name'],
            'email'            => $row['email'],
            'department'       => $row['department'],
            'role'             => $row['role'],
            'assigned_section' => $row['assigned_section'],
            'contact_number'   => $row['contact_number'],
        ];
    }, $all);

    respond(200, [
        'success'      => true,
        'count'        => count($coordinators),
        'coordinators' => $coordinators,
    ]);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
