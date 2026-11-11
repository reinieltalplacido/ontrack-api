<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

$section   = trim((string)($_GET['section'] ?? ''));
$course    = trim((string)($_GET['course'] ?? ''));
$college   = trim((string)($_GET['college'] ?? ''));
$campus    = trim((string)($_GET['campus'] ?? ($_GET['school_campus'] ?? '')));
$yearLevel = intval($_GET['year_level'] ?? 4);

try {
    $pdo = get_db_connection();

    if ($section !== '' || $course !== '' || $campus !== '') {
        [$resolvedDept, $courseCode] = resolve_college_and_code($course, $college);
        $canonSec = compute_canonical_section($course, $section, $yearLevel);

        // Extract section letter (e.g. "4-A" -> "A")
        preg_match('/[A-Z0-9]+$/', strtoupper(trim($section)), $matches);
        $secLetter = !empty($matches) ? $matches[0] : 'A';
        $secEnd = '%-' . $secLetter;
        $courseSecPattern = $courseCode . ' %-' . $secLetter;

        $coordinator = null;

        // --- CAMPUS-AWARE SEARCH (If campus provided) ---
        if ($campus !== '') {
            // Priority 1: Match campus + exact canonical section (e.g. San Isidro Campus + BSIT 4-A)
            $stmt = $pdo->prepare(
                'SELECT id, employee_id, full_name, email, department, campus, role, assigned_section, contact_number 
                 FROM professors 
                 WHERE campus = :campus AND assigned_section = :exactSec 
                 LIMIT 1'
            );
            $stmt->execute(['campus' => $campus, 'exactSec' => $canonSec]);
            $coordinator = $stmt->fetch();

            // Priority 2: Match campus + course code and section letter (e.g. BSIT %-A)
            if (!$coordinator) {
                $stmt = $pdo->prepare(
                    'SELECT id, employee_id, full_name, email, department, campus, role, assigned_section, contact_number 
                     FROM professors 
                     WHERE campus = :campus AND assigned_section LIKE :courseSecPattern 
                     ORDER BY id ASC LIMIT 1'
                );
                $stmt->execute(['campus' => $campus, 'courseSecPattern' => $courseSecPattern]);
                $coordinator = $stmt->fetch();
            }

            // Priority 3: Match campus + college department and section letter
            if (!$coordinator && $resolvedDept !== '') {
                $stmt = $pdo->prepare(
                    'SELECT id, employee_id, full_name, email, department, campus, role, assigned_section, contact_number 
                     FROM professors 
                     WHERE campus = :campus AND department = :dept AND assigned_section LIKE :secEnd 
                     ORDER BY id ASC LIMIT 1'
                );
                $stmt->execute(['campus' => $campus, 'dept' => $resolvedDept, 'secEnd' => $secEnd]);
                $coordinator = $stmt->fetch();
            }

            // Priority 4: Match campus + college department
            if (!$coordinator && $resolvedDept !== '') {
                $stmt = $pdo->prepare(
                    'SELECT id, employee_id, full_name, email, department, campus, role, assigned_section, contact_number 
                     FROM professors 
                     WHERE campus = :campus AND department = :dept 
                     ORDER BY id ASC LIMIT 1'
                );
                $stmt->execute(['campus' => $campus, 'dept' => $resolvedDept]);
                $coordinator = $stmt->fetch();
            }

            // Priority 5: Match any coordinator from this campus
            if (!$coordinator) {
                $stmt = $pdo->prepare(
                    'SELECT id, employee_id, full_name, email, department, campus, role, assigned_section, contact_number 
                     FROM professors 
                     WHERE campus = :campus 
                     ORDER BY id ASC LIMIT 1'
                );
                $stmt->execute(['campus' => $campus]);
                $coordinator = $stmt->fetch();
            }
        }

        // --- CROSS-CAMPUS FALLBACK (If campus not matched or not provided) ---
        // Priority 6: Exact canonical section across all campuses
        if (!$coordinator) {
            $stmt = $pdo->prepare(
                'SELECT id, employee_id, full_name, email, department, campus, role, assigned_section, contact_number 
                 FROM professors 
                 WHERE assigned_section = :exactSec 
                 ORDER BY id ASC LIMIT 1'
            );
            $stmt->execute(['exactSec' => $canonSec]);
            $coordinator = $stmt->fetch();
        }

        // Priority 7: Match by course code and section letter
        if (!$coordinator) {
            $stmt = $pdo->prepare(
                'SELECT id, employee_id, full_name, email, department, campus, role, assigned_section, contact_number 
                 FROM professors 
                 WHERE assigned_section LIKE :courseSecPattern 
                 ORDER BY id ASC LIMIT 1'
            );
            $stmt->execute(['courseSecPattern' => $courseSecPattern]);
            $coordinator = $stmt->fetch();
        }

        // Priority 8: Match by college department and section letter
        if (!$coordinator && $resolvedDept !== '') {
            $stmt = $pdo->prepare(
                'SELECT id, employee_id, full_name, email, department, campus, role, assigned_section, contact_number 
                 FROM professors 
                 WHERE department = :dept AND assigned_section LIKE :secEnd 
                 ORDER BY id ASC LIMIT 1'
            );
            $stmt->execute(['dept' => $resolvedDept, 'secEnd' => $secEnd]);
            $coordinator = $stmt->fetch();
        }

        // Priority 9: Match any coordinator from department
        if (!$coordinator && $resolvedDept !== '') {
            $stmt = $pdo->prepare(
                'SELECT id, employee_id, full_name, email, department, campus, role, assigned_section, contact_number 
                 FROM professors 
                 WHERE department = :dept 
                 ORDER BY id ASC LIMIT 1'
            );
            $stmt->execute(['dept' => $resolvedDept]);
            $coordinator = $stmt->fetch();
        }

        // Priority 10: Fallback to lead admin coordinator
        if (!$coordinator) {
            $fallbackStmt = $pdo->query('SELECT id, employee_id, full_name, email, department, campus, role, assigned_section, contact_number FROM professors ORDER BY id ASC LIMIT 1');
            $coordinator = $fallbackStmt->fetch();
        }

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
                    'campus'           => $coordinator['campus'] ?? ($campus ?: 'NEUST - Sumacab Campus'),
                    'role'             => $coordinator['role'],
                    'assigned_section' => $coordinator['assigned_section'] ?: $canonSec,
                    'contact_number'   => $coordinator['contact_number'],
                ],
                'canonical_section' => $canonSec,
                'course_code'       => $courseCode,
                'department'        => $resolvedDept,
                'campus'            => $coordinator['campus'] ?? $campus,
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
