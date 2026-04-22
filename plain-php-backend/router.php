<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/bootstrap.php';

cors_headers();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$pdo = null;

function local_applicants_path(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'local_applicants.json';
}

function local_management_path(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'local_spes_management.json';
}

function local_deployment_path(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'local_deployment.json';
}

function local_reset_codes_path(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'local_password_resets.json';
}

function local_auth_overrides_path(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'local_auth_overrides.json';
}

function read_local_applicants(): array
{
    $path = local_applicants_path();
    if (!file_exists($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : [];
}

function write_local_applicants(array $rows): void
{
    $path = local_applicants_path();
    file_put_contents($path, json_encode(array_values($rows), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}

function read_local_records(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : [];
}

function write_local_records(string $path, array $rows): void
{
    file_put_contents($path, json_encode(array_values($rows), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}

function read_local_map(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : [];
}

function write_local_map(string $path, array $map): void
{
    file_put_contents($path, json_encode($map, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}

function parse_reset_datetime_to_timestamp(?string $value): ?int
{
    $raw = trim((string) $value);
    if ($raw === '') {
        return null;
    }

    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw, new DateTimeZone('Asia/Manila'));
    if (!$dt) {
        return null;
    }

    return $dt->getTimestamp();
}

function reset_expiry_at(int $minutes): string
{
    $dt = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
    return $dt->modify('+' . max(1, $minutes) . ' minutes')->format('Y-m-d H:i:s');
}

function applicants_paginated_response(array $allRows, int $page, int $perPage): array
{
    $total = count($allRows);
    $lastPage = (int) max(1, ceil($total / max(1, $perPage)));
    $page = min($page, $lastPage);
    $offset = ($page - 1) * $perPage;
    $slice = array_slice($allRows, $offset, $perPage);

    return [
        'current_page' => $page,
        'data' => array_values($slice),
        'first_page_url' => 'http://127.0.0.1:8000/api/spes-management/applicants?page=1',
        'from' => $total > 0 ? $offset + 1 : null,
        'per_page' => $perPage,
        'to' => $total > 0 ? min($offset + $perPage, $total) : null,
        'total' => $total,
        'last_page' => $lastPage,
        'last_page_url' => 'http://127.0.0.1:8000/api/spes-management/applicants?page=' . $lastPage,
        'next_page_url' => ($offset + $perPage) < $total ? 'http://127.0.0.1:8000/api/spes-management/applicants?page=' . ($page + 1) : null,
        'prev_page_url' => $page > 1 ? 'http://127.0.0.1:8000/api/spes-management/applicants?page=' . ($page - 1) : null,
        'path' => 'http://127.0.0.1:8000/api/spes-management/applicants',
        'links' => [
            [
                'url' => $page > 1 ? 'http://127.0.0.1:8000/api/spes-management/applicants?page=' . ($page - 1) : null,
                'label' => '&laquo; Previous',
                'page' => $page > 1 ? ($page - 1) : null,
                'active' => false,
            ],
            [
                'url' => 'http://127.0.0.1:8000/api/spes-management/applicants?page=' . $page,
                'label' => (string) $page,
                'page' => $page,
                'active' => true,
            ],
            [
                'url' => ($offset + $perPage) < $total ? 'http://127.0.0.1:8000/api/spes-management/applicants?page=' . ($page + 1) : null,
                'label' => 'Next &raquo;',
                'page' => ($offset + $perPage) < $total ? ($page + 1) : null,
                'active' => false,
            ],
        ],
    ];
}

function normalize_status_value($value): string
{
    return strtolower(trim((string) ($value ?? '')));
}

function map_management_to_applicant_row(array $row): array
{
    return [
        'id' => (int) ($row['applicant_id'] ?? $row['id'] ?? 0),
        'control_no' => $row['control_no'] ?? null,
        'source' => $row['source'] ?? 'Walk-in',
        'status' => $row['status'] ?? 'Approved',
        'last_name' => $row['last_name'] ?? null,
        'first_name' => $row['first_name'] ?? null,
        'middle_name' => $row['middle_name'] ?? null,
        'street' => $row['street'] ?? null,
        'district' => $row['district'] ?? null,
        'city' => $row['city'] ?? null,
        'province' => $row['province'] ?? null,
        'address' => $row['address'] ?? null,
        'dob' => $row['dob'] ?? null,
        'age' => $row['age'] ?? null,
        'sex' => $row['sex'] ?? null,
        'place_of_birth' => $row['place_of_birth'] ?? null,
        'email' => $row['email'] ?? null,
        'contact_number' => $row['contact_number'] ?? null,
        'fb_account' => $row['fb_account'] ?? null,
        'is_scholar' => $row['is_scholar'] ?? null,
        'scholarship_name' => $row['scholarship_name'] ?? null,
        'is_4ps' => $row['is_4ps'] ?? null,
        'is_pwd' => $row['is_pwd'] ?? null,
        'elem_school' => $row['elem_school'] ?? null,
        'jhs_school' => $row['jhs_school'] ?? null,
        'shs_school' => $row['shs_school'] ?? null,
        'shs_track' => $row['shs_track'] ?? null,
        'college_school' => $row['college_school'] ?? null,
        'college_course' => $row['college_course'] ?? null,
        'father_name' => $row['father_name'] ?? null,
        'father_age' => $row['father_age'] ?? null,
        'father_occupation' => $row['father_occupation'] ?? null,
        'father_remarks' => $row['father_remarks'] ?? null,
        'mother_name' => $row['mother_name'] ?? null,
        'mother_age' => $row['mother_age'] ?? null,
        'mother_occupation' => $row['mother_occupation'] ?? null,
        'mother_remarks' => $row['mother_remarks'] ?? null,
        'flagged' => 0,
        'flag_reason' => null,
        'created_at' => $row['approved_at'] ?? $row['created_at'] ?? null,
        'updated_at' => $row['updated_at'] ?? null,
    ];
}

function map_management_to_summary_row(array $row): array
{
    return [
        'id' => (int) ($row['applicant_id'] ?? $row['id'] ?? 0),
        'control_no' => $row['control_no'] ?? null,
        'source' => $row['source'] ?? 'Walk-in',
        'status' => $row['status'] ?? 'Approved',
        'last_name' => $row['last_name'] ?? null,
        'first_name' => $row['first_name'] ?? null,
        'middle_name' => $row['middle_name'] ?? null,
        'district' => $row['district'] ?? null,
        'age' => $row['age'] ?? null,
        'sex' => $row['sex'] ?? null,
        'email' => $row['email'] ?? null,
        'college_school' => $row['college_school'] ?? null,
        'college_course' => $row['college_course'] ?? null,
        'flagged' => 0,
        'flag_reason' => null,
        'created_at' => $row['approved_at'] ?? $row['created_at'] ?? null,
    ];
}

function normalize_name_key($value): string
{
    $text = strtolower(trim((string) ($value ?? '')));
    if ($text === '') {
        return '';
    }

    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', (string) $text);

    return trim((string) $text);
}

function surname_from_full_name($value): string
{
    $name = trim((string) ($value ?? ''));
    if ($name === '') {
        return '';
    }

    $parts = explode(',', $name, 2);
    if (count($parts) > 1) {
        return normalize_name_key($parts[0]);
    }

    $chunks = preg_split('/\s+/', $name);

    return normalize_name_key(is_array($chunks) && count($chunks) > 0 ? $chunks[0] : $name);
}

function duplicate_flag_decision(string $surnameKey, string $parentKey, array $existing): array
{
    if ($surnameKey === '' && $parentKey === '') {
        return ['flagged' => false, 'reason' => null];
    }

    foreach ($existing as $row) {
        $existingSurname = normalize_name_key($row['surname'] ?? '');
        $existingParent = normalize_name_key($row['parent'] ?? '');

        if ($surnameKey !== '' && $parentKey !== '' && $existingSurname === $surnameKey && $existingParent === $parentKey) {
            return ['flagged' => true, 'reason' => 'Duplicate surname and parent name'];
        }
    }

    foreach ($existing as $row) {
        $existingParent = normalize_name_key($row['parent'] ?? '');
        if ($parentKey !== '' && $existingParent === $parentKey) {
            return ['flagged' => true, 'reason' => 'Same parent name as existing record'];
        }
    }

    foreach ($existing as $row) {
        $existingSurname = normalize_name_key($row['surname'] ?? '');
        if ($surnameKey !== '' && $existingSurname === $surnameKey) {
            return ['flagged' => true, 'reason' => 'Duplicate surname'];
        }
    }

    return ['flagged' => false, 'reason' => null];
}

function duplicate_check_mysql(PDO $pdo, string $surnameKey, string $parentKey): array
{
    $existing = [];

    $pending = $pdo->query('SELECT last_name, mother_name, father_name FROM spes_applicants WHERE archived = 0 ORDER BY id ASC')->fetchAll();
    foreach ($pending as $row) {
        $existing[] = [
            'surname' => $row['last_name'] ?? '',
            'parent' => ($row['mother_name'] ?? '') !== '' ? ($row['mother_name'] ?? '') : ($row['father_name'] ?? ''),
        ];
    }

    $management = $pdo->query('SELECT last_name, mother_name, father_name FROM spes_management_records WHERE archived = 0 ORDER BY id ASC')->fetchAll();
    foreach ($management as $row) {
        $existing[] = [
            'surname' => $row['last_name'] ?? '',
            'parent' => ($row['mother_name'] ?? '') !== '' ? ($row['mother_name'] ?? '') : ($row['father_name'] ?? ''),
        ];
    }

    $deployment = $pdo->query('SELECT full_name FROM deployment_records WHERE archived = 0 ORDER BY id ASC')->fetchAll();
    foreach ($deployment as $row) {
        $existing[] = [
            'surname' => surname_from_full_name($row['full_name'] ?? ''),
            'parent' => '',
        ];
    }

    return duplicate_flag_decision($surnameKey, $parentKey, $existing);
}

function duplicate_check_local(array $pendingRows, array $managementRows, array $deploymentRows, string $surnameKey, string $parentKey): array
{
    $existing = [];

    foreach ($pendingRows as $row) {
        if (((int) ($row['archived'] ?? 0)) === 1) {
            continue;
        }
        $existing[] = [
            'surname' => $row['last_name'] ?? '',
            'parent' => ($row['mother_name'] ?? '') !== '' ? ($row['mother_name'] ?? '') : ($row['father_name'] ?? ''),
        ];
    }

    foreach ($managementRows as $row) {
        if (((int) ($row['archived'] ?? 0)) === 1) {
            continue;
        }
        $existing[] = [
            'surname' => $row['last_name'] ?? '',
            'parent' => ($row['mother_name'] ?? '') !== '' ? ($row['mother_name'] ?? '') : ($row['father_name'] ?? ''),
        ];
    }

    foreach ($deploymentRows as $row) {
        if (((int) ($row['archived'] ?? 0)) === 1) {
            continue;
        }
        $existing[] = [
            'surname' => surname_from_full_name($row['full_name'] ?? ''),
            'parent' => '',
        ];
    }

    return duplicate_flag_decision($surnameKey, $parentKey, $existing);
}

function fallback_login_user(string $email): ?array
{
    $defaults = [
        ['email' => 'admin@ccc.edu.ph', 'password' => 'Admin@12345'],
        ['email' => 'employee@ccc.edu.ph', 'password' => 'Employee@12345'],
        ['email' => 'student@ccc.edu.ph', 'password' => 'Student@12345'],
        ['email' => 'khenvergara29@gmail.com', 'password' => 'Student@12345'],
    ];

    foreach ($defaults as $user) {
        if (strcasecmp($email, (string) $user['email']) === 0) {
            return $user;
        }
    }

    return null;
}

function fallback_email_exists(string $email): bool
{
    if (fallback_login_user($email)) {
        return true;
    }

    $overrides = read_local_map(local_auth_overrides_path());

    return isset($overrides[strtolower($email)]);
}

function verify_fallback_password(string $email, string $password): bool
{
    $overrides = read_local_map(local_auth_overrides_path());
    $key = strtolower($email);
    if (isset($overrides[$key]) && is_string($overrides[$key]) && $overrides[$key] !== '') {
        return password_verify($password, $overrides[$key]);
    }

    $fallback = fallback_login_user($email);
    if (!$fallback) {
        return false;
    }

    return hash_equals((string) $fallback['password'], $password);
}

function set_fallback_password(string $email, string $password): void
{
    $overrides = read_local_map(local_auth_overrides_path());
    $overrides[strtolower($email)] = password_hash($password, PASSWORD_BCRYPT);
    write_local_map(local_auth_overrides_path(), $overrides);
}

function read_local_reset_record(string $email): ?array
{
    $all = read_local_map(local_reset_codes_path());
    $key = strtolower($email);
    if (!isset($all[$key]) || !is_array($all[$key])) {
        return null;
    }

    return $all[$key];
}

function write_local_reset_record(string $email, array $record): void
{
    $all = read_local_map(local_reset_codes_path());
    $all[strtolower($email)] = $record;
    write_local_map(local_reset_codes_path(), $all);
}

function login_profile_for_email(string $email): array
{
    if (strcasecmp($email, ADMIN_EMAIL) === 0) {
        return [
            'role' => 'admin',
            'redirect' => 'Coordinator/cDashboard.html',
        ];
    }

    if (strcasecmp($email, EMPLOYEE_EMAIL) === 0) {
        return [
            'role' => 'employee',
            'redirect' => 'Employee/eDashboard.html',
        ];
    }

    return [
        'role' => 'student',
        'redirect' => 'Student/sbDashboard.html',
    ];
}

function login_profile_for_role(string $role): array
{
    $normalized = strtolower(trim($role));
    if ($normalized === 'admin') {
        return ['role' => 'admin', 'redirect' => 'Coordinator/cDashboard.html'];
    }
    if ($normalized === 'employee') {
        return ['role' => 'employee', 'redirect' => 'Employee/eDashboard.html'];
    }

    return ['role' => 'student', 'redirect' => 'Student/sbDashboard.html'];
}

function persist_login_session(string $email, string $role): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    session_regenerate_id(true);
    $_SESSION['auth'] = [
        'email' => $email,
        'role' => strtolower($role),
        'logged_in' => true,
        'logged_in_at' => now_utc(),
    ];
}

function mysql_archive_by_applicant(PDO $pdo, int $applicantId, string $now): array
{
    $pdo->beginTransaction();

    $markPending = $pdo->prepare('UPDATE spes_applicants SET archived = 1, status = "Archived", updated_at = :now WHERE id = :id AND archived = 0');
    $markPending->execute([':now' => $now, ':id' => $applicantId]);

    $markManagement = $pdo->prepare('UPDATE spes_management_records SET archived = 1, status = "Archived", updated_at = :now WHERE applicant_id = :id AND archived = 0');
    $markManagement->execute([':now' => $now, ':id' => $applicantId]);

    $markDeployment = $pdo->prepare('UPDATE deployment_records SET archived = 1, deployment_status = "Archived", updated_at = :now WHERE applicant_id = :id AND archived = 0');
    $markDeployment->execute([':now' => $now, ':id' => $applicantId]);

    $pdo->commit();

    return [
        'pending' => $markPending->rowCount(),
        'management' => $markManagement->rowCount(),
        'deployment' => $markDeployment->rowCount(),
    ];
}

function local_archive_by_applicant(int $applicantId, string $now): array
{
    $pendingRows = read_local_applicants();
    $managementRows = read_local_records(local_management_path());
    $deploymentRows = read_local_records(local_deployment_path());

    $pendingUpdated = 0;
    foreach ($pendingRows as &$row) {
        if (((int) ($row['id'] ?? 0)) === $applicantId && ((int) ($row['archived'] ?? 0)) === 0) {
            $row['archived'] = 1;
            $row['status'] = 'Archived';
            $row['updated_at'] = $now;
            $pendingUpdated++;
        }
    }
    unset($row);

    $managementUpdated = 0;
    foreach ($managementRows as &$row) {
        if (((int) ($row['applicant_id'] ?? 0)) === $applicantId && ((int) ($row['archived'] ?? 0)) === 0) {
            $row['archived'] = 1;
            $row['status'] = 'Archived';
            $row['updated_at'] = $now;
            $managementUpdated++;
        }
    }
    unset($row);

    $deploymentUpdated = 0;
    foreach ($deploymentRows as &$row) {
        if (((int) ($row['applicant_id'] ?? 0)) === $applicantId && ((int) ($row['archived'] ?? 0)) === 0) {
            $row['archived'] = 1;
            $row['deployment_status'] = 'Archived';
            $row['updated_at'] = $now;
            $deploymentUpdated++;
        }
    }
    unset($row);

    write_local_applicants($pendingRows);
    write_local_records(local_management_path(), $managementRows);
    write_local_records(local_deployment_path(), $deploymentRows);

    return [
        'pending' => $pendingUpdated,
        'management' => $managementUpdated,
        'deployment' => $deploymentUpdated,
    ];
}

try {
    if ($method === 'POST' && $path === '/api/login') {
        $body = parse_json_body();
        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($email === '' || $password === '') {
            send_json(422, ['message' => 'Email and password are required']);
        }

        if (!valid_email($email)) {
            send_json(422, ['message' => 'Invalid email format']);
        }

        $account = null;
        try {
            $pdo = $pdo instanceof PDO ? $pdo : pdo();

            $stmt = $pdo->prepare('SELECT id, email, password, "users" AS source_table FROM users WHERE email = :email OR name = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $account = $stmt->fetch();

            if (!$account) {
                $studentStmt = $pdo->prepare('SELECT id, email, password, "students" AS source_table FROM students WHERE email = :email OR student_no = :email OR name = :email LIMIT 1');
                $studentStmt->execute([':email' => $email]);
                $account = $studentStmt->fetch();
            }
        } catch (Throwable $e) {
            $fallback = fallback_login_user($email);
            if ($fallback && verify_fallback_password((string) $fallback['email'], $password)) {
                $profile = login_profile_for_email((string) $fallback['email']);
                persist_login_session((string) $fallback['email'], (string) $profile['role']);
                send_json(200, [
                    'message' => 'Login successful (offline mode)',
                    'role' => $profile['role'],
                    'redirect' => $profile['redirect'],
                ]);
            }

            send_json(503, ['message' => 'Database unavailable. Please start MySQL and try again.']);
        }

        if (!$account || !password_verify($password, (string) $account['password'])) {
            $fallback = fallback_login_user($email);
            if (!$fallback || !verify_fallback_password((string) $fallback['email'], $password)) {
                send_json(401, ['message' => 'Invalid email or password']);
            }

            $profile = login_profile_for_email((string) $fallback['email']);
            persist_login_session((string) $fallback['email'], (string) $profile['role']);
            send_json(200, [
                'message' => 'Login successful (fallback mode)',
                'role' => $profile['role'],
                'redirect' => $profile['redirect'],
            ]);
        }

        $loginEmail = (string) ($account['email'] ?? $email);
        $sourceTable = (string) ($account['source_table'] ?? 'users');
        if ($sourceTable === 'students') {
            $profile = login_profile_for_role('student');
        } else {
            $profile = login_profile_for_email($loginEmail);
        }

        persist_login_session($loginEmail, (string) $profile['role']);
        send_json(200, [
            'message' => 'Login successful',
            'role' => $profile['role'],
            'redirect' => $profile['redirect'],
        ]);
    }

    if ($method === 'POST' && $path === '/api/forgot-password/send-code') {
        $body = parse_json_body();
        $email = trim((string) ($body['email'] ?? ''));

        if ($email === '' || !valid_email($email)) {
            send_json(422, ['message' => 'Valid email is required']);
        }

        try {
            $pdo = $pdo instanceof PDO ? $pdo : pdo();

            $userStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $userStmt->execute([':email' => $email]);
            if (!$userStmt->fetch()) {
                send_json(404, ['message' => 'Email not found']);
            }

            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $now = now_utc();
            $expiresAt = reset_expiry_at(15);

            $upsert = $pdo->prepare(
                'INSERT INTO password_reset_codes (email, verification_code, expires_at, used_at, reset_token, token_expires_at, created_at, updated_at)
                 VALUES (:email, :verification_code, :expires_at, NULL, NULL, NULL, :created_at, :updated_at)
                 ON DUPLICATE KEY UPDATE
                verification_code=VALUES(verification_code),
                expires_at=VALUES(expires_at),
                    used_at=NULL,
                    reset_token=NULL,
                    token_expires_at=NULL,
                updated_at=VALUES(updated_at)'
            );
            $upsert->execute([
                ':email' => $email,
                ':verification_code' => $code,
                ':expires_at' => $expiresAt,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);

            send_verification_code_email($email, $code, $expiresAt);
            send_json(200, ['message' => 'Verification code sent! Please check your email.']);
        } catch (Throwable $dbOrMailError) {
            $message = $dbOrMailError->getMessage();
            if (stripos($message, 'SQLSTATE') !== false || stripos($message, 'Database') !== false || stripos($message, 'Connection') !== false) {
                if (!fallback_email_exists($email)) {
                    send_json(404, ['message' => 'Email not found']);
                }

                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $now = now_utc();
                $expiresAt = reset_expiry_at(15);
                write_local_reset_record($email, [
                    'email' => strtolower($email),
                    'verification_code' => $code,
                    'expires_at' => $expiresAt,
                    'used_at' => null,
                    'reset_token' => null,
                    'token_expires_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                try {
                    send_verification_code_email($email, $code, $expiresAt);
                    send_json(200, ['message' => 'Verification code sent! Please check your email.']);
                } catch (Throwable $mailError) {
                    send_json(200, ['message' => 'Verification code generated in offline mode. Use this code: ' . $code]);
                }
            }

            send_json(500, ['message' => 'Unable to send verification email. Please check MAIL settings in plain-php-backend/.env']);
        }
    }

    if ($method === 'POST' && $path === '/api/forgot-password/verify-code') {
        $body = parse_json_body();
        $email = trim((string) ($body['email'] ?? ''));
        $code = trim((string) ($body['verification_code'] ?? ''));

        if ($email === '' || !valid_email($email) || strlen($code) !== 6) {
            send_json(422, ['message' => 'Invalid verification request']);
        }

        try {
            $pdo = $pdo instanceof PDO ? $pdo : pdo();

            $stmt = $pdo->prepare('SELECT * FROM password_reset_codes WHERE email = :email AND verification_code = :verification_code LIMIT 1');
            $stmt->execute([':email' => $email, ':verification_code' => $code]);
            $record = $stmt->fetch();

            if (!$record) {
                send_json(422, ['message' => 'Invalid verification code']);
            }

            if (!empty($record['used_at'])) {
                send_json(422, ['message' => 'Verification code has already been used']);
            }

            $expires = parse_reset_datetime_to_timestamp((string) ($record['expires_at'] ?? ''));
            if (!$expires || $expires < time()) {
                send_json(422, ['message' => 'Verification code has expired']);
            }

            $plainToken = bin2hex(random_bytes(32));
            $hashedToken = hash('sha256', $plainToken);
            $now = now_utc();
            $tokenExpires = reset_expiry_at(15);

            $update = $pdo->prepare(
                'UPDATE password_reset_codes
                 SET used_at = :used_at, reset_token = :reset_token, token_expires_at = :token_expires_at, updated_at = :updated_at
                 WHERE email = :email'
            );
            $update->execute([
                ':used_at' => $now,
                ':reset_token' => $hashedToken,
                ':token_expires_at' => $tokenExpires,
                ':updated_at' => $now,
                ':email' => $email,
            ]);

            send_json(200, ['message' => 'Code verified successfully', 'reset_token' => $plainToken]);
        } catch (Throwable $dbError) {
            $record = read_local_reset_record($email);
            if (!$record || !hash_equals((string) ($record['verification_code'] ?? ''), $code)) {
                send_json(422, ['message' => 'Invalid verification code']);
            }

            if (!empty($record['used_at'])) {
                send_json(422, ['message' => 'Verification code has already been used']);
            }

            $expires = parse_reset_datetime_to_timestamp((string) ($record['expires_at'] ?? ''));
            if (!$expires || $expires < time()) {
                send_json(422, ['message' => 'Verification code has expired']);
            }

            $plainToken = bin2hex(random_bytes(32));
            $record['used_at'] = now_utc();
            $record['reset_token'] = hash('sha256', $plainToken);
            $record['token_expires_at'] = reset_expiry_at(15);
            $record['updated_at'] = now_utc();
            write_local_reset_record($email, $record);

            send_json(200, ['message' => 'Code verified successfully', 'reset_token' => $plainToken]);
        }
    }

    if ($method === 'POST' && $path === '/api/forgot-password/reset') {
        $body = parse_json_body();
        $email = trim((string) ($body['email'] ?? ''));
        $resetToken = trim((string) ($body['reset_token'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        $passwordConfirmation = (string) ($body['password_confirmation'] ?? '');

        if ($email === '' || !valid_email($email) || $resetToken === '') {
            send_json(422, ['message' => 'Invalid reset request']);
        }

        if ($password === '' || strlen($password) < 8) {
            send_json(422, ['message' => 'Password must be at least 8 characters']);
        }

        if ($password !== $passwordConfirmation) {
            send_json(422, ['message' => 'Password confirmation does not match']);
        }

        try {
            $pdo = $pdo instanceof PDO ? $pdo : pdo();

            $userStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $userStmt->execute([':email' => $email]);
            $user = $userStmt->fetch();
            if (!$user) {
                send_json(404, ['message' => 'Email not found']);
            }

            $tokenStmt = $pdo->prepare('SELECT * FROM password_reset_codes WHERE email = :email AND reset_token = :reset_token LIMIT 1');
            $tokenStmt->execute([':email' => $email, ':reset_token' => hash('sha256', $resetToken)]);
            $record = $tokenStmt->fetch();

            if (!$record) {
                send_json(422, ['message' => 'Invalid or expired reset token']);
            }

            $tokenExpires = parse_reset_datetime_to_timestamp((string) ($record['token_expires_at'] ?? ''));
            if (!$tokenExpires || $tokenExpires < time()) {
                send_json(422, ['message' => 'Invalid or expired reset token']);
            }

            $now = now_utc();

            $updateUser = $pdo->prepare('UPDATE users SET password = :password, updated_at = :updated_at WHERE email = :email');
            $updateUser->execute([
                ':password' => password_hash($password, PASSWORD_BCRYPT),
                ':updated_at' => $now,
                ':email' => $email,
            ]);

            $clearToken = $pdo->prepare(
                'UPDATE password_reset_codes
                 SET verification_code = NULL, expires_at = NULL, used_at = :used_at, reset_token = NULL, token_expires_at = NULL, updated_at = :updated_at
                 WHERE email = :email'
            );
            $clearToken->execute([
                ':used_at' => $now,
                ':updated_at' => $now,
                ':email' => $email,
            ]);

            send_json(200, ['message' => 'Password reset successful']);
        } catch (Throwable $dbError) {
            if (!fallback_email_exists($email)) {
                send_json(404, ['message' => 'Email not found']);
            }

            $record = read_local_reset_record($email);
            if (!$record || !hash_equals((string) ($record['reset_token'] ?? ''), hash('sha256', $resetToken))) {
                send_json(422, ['message' => 'Invalid or expired reset token']);
            }

            $tokenExpires = parse_reset_datetime_to_timestamp((string) ($record['token_expires_at'] ?? ''));
            if (!$tokenExpires || $tokenExpires < time()) {
                send_json(422, ['message' => 'Invalid or expired reset token']);
            }

            set_fallback_password($email, $password);

            $record['verification_code'] = null;
            $record['expires_at'] = null;
            $record['used_at'] = now_utc();
            $record['reset_token'] = null;
            $record['token_expires_at'] = null;
            $record['updated_at'] = now_utc();
            write_local_reset_record($email, $record);

            send_json(200, ['message' => 'Password reset successful']);
        }
    }

    if ($method === 'POST' && $path === '/api/add-applicant') {
        $requiredErrors = [
            required_field($_POST, 'lastName', 'Last name'),
            required_field($_POST, 'firstName', 'First name'),
            required_field($_POST, 'dob', 'Date of birth'),
            required_field($_POST, 'sex', 'Sex'),
            required_field($_POST, 'email', 'Email'),
            required_field($_POST, 'contactNumber', 'Contact number'),
            required_field($_POST, 'collegeSchool', 'College/University'),
            required_field($_POST, 'collegeCourse', 'Course/Program'),
        ];

        $firstError = null;
        foreach ($requiredErrors as $err) {
            if (is_string($err) && $err !== '') {
                $firstError = $err;
                break;
            }
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        if ($firstError !== null) {
            send_json(422, ['message' => $firstError]);
        }

        if (!valid_email($email)) {
            send_json(422, ['message' => 'Email must be a valid email address']);
        }

        $lastNameValue = trim((string) ($_POST['lastName'] ?? ''));
        $motherNameValue = trim((string) ($_POST['motherName'] ?? ''));
        $fatherNameValue = trim((string) ($_POST['fatherName'] ?? ''));
        $parentNameValue = $motherNameValue !== '' ? $motherNameValue : $fatherNameValue;
        $surnameKey = normalize_name_key($lastNameValue);
        $parentKey = normalize_name_key($parentNameValue);

        $address = trim((string) ($_POST['address'] ?? ''));
        $addressParts = parse_address($address !== '' ? $address : null);
        $now = now_utc();

        try {
            $pdo = $pdo instanceof PDO ? $pdo : pdo();

            $existing = $pdo->prepare('SELECT id FROM spes_applicants WHERE email = :email LIMIT 1');
            $existing->execute([':email' => $email]);
            if ($existing->fetch()) {
                send_json(422, ['message' => 'The email has already been taken.']);
            }

            $flagDecision = duplicate_check_mysql($pdo, $surnameKey, $parentKey);
            $isFlagged = $flagDecision['flagged'] ? 1 : 0;
            $flagReason = $flagDecision['reason'];

            $insert = $pdo->prepare(
                'INSERT INTO spes_applicants (
                    control_no, source, status, last_name, first_name, middle_name,
                    street, district, city, province, address,
                    dob, age, sex, place_of_birth, email, contact_number, fb_account,
                    is_scholar, scholarship_name, is_4ps, is_pwd,
                    elem_school, jhs_school, shs_school, shs_track,
                    college_school, college_course,
                    father_name, father_age, father_occupation, father_remarks,
                    mother_name, mother_age, mother_occupation, mother_remarks,
                    flagged, flag_reason,
                    created_at, updated_at
                ) VALUES (
                    :control_no, :source, :status, :last_name, :first_name, :middle_name,
                    :street, :district, :city, :province, :address,
                    :dob, :age, :sex, :place_of_birth, :email, :contact_number, :fb_account,
                    :is_scholar, :scholarship_name, :is_4ps, :is_pwd,
                    :elem_school, :jhs_school, :shs_school, :shs_track,
                    :college_school, :college_course,
                    :father_name, :father_age, :father_occupation, :father_remarks,
                    :mother_name, :mother_age, :mother_occupation, :mother_remarks,
                    :flagged, :flag_reason,
                    :created_at, :updated_at
                )'
            );

            $controlNo = generate_control_no($pdo);
            $insert->execute([
                ':control_no' => $controlNo,
                ':source' => 'Walk-in',
                ':status' => 'Pending',
                ':last_name' => trim((string) ($_POST['lastName'] ?? '')),
                ':first_name' => trim((string) ($_POST['firstName'] ?? '')),
                ':middle_name' => trim((string) ($_POST['middleName'] ?? '')) ?: null,
                ':street' => $addressParts['street'],
                ':district' => $addressParts['district'],
                ':city' => $addressParts['city'],
                ':province' => $addressParts['province'],
                ':address' => $address !== '' ? $address : null,
                ':dob' => trim((string) ($_POST['dob'] ?? '')),
                ':age' => trim((string) ($_POST['age'] ?? '')) !== '' ? (int) $_POST['age'] : null,
                ':sex' => trim((string) ($_POST['sex'] ?? '')),
                ':place_of_birth' => trim((string) ($_POST['placeOfBirth'] ?? '')) ?: null,
                ':email' => $email,
                ':contact_number' => trim((string) ($_POST['contactNumber'] ?? '')),
                ':fb_account' => trim((string) ($_POST['fbAccount'] ?? '')) ?: null,
                ':is_scholar' => trim((string) ($_POST['isScholar'] ?? '')) ?: null,
                ':scholarship_name' => trim((string) ($_POST['scholarshipName'] ?? '')) ?: null,
                ':is_4ps' => trim((string) ($_POST['is4ps'] ?? '')) ?: null,
                ':is_pwd' => trim((string) ($_POST['isPwd'] ?? '')) ?: null,
                ':elem_school' => trim((string) ($_POST['elemSchool'] ?? '')) ?: null,
                ':jhs_school' => trim((string) ($_POST['jhsSchool'] ?? '')) ?: null,
                ':shs_school' => trim((string) ($_POST['shsSchool'] ?? '')) ?: null,
                ':shs_track' => trim((string) ($_POST['shsTrack'] ?? '')) ?: null,
                ':college_school' => trim((string) ($_POST['collegeSchool'] ?? '')),
                ':college_course' => trim((string) ($_POST['collegeCourse'] ?? '')),
                ':father_name' => trim((string) ($_POST['fatherName'] ?? '')) ?: null,
                ':father_age' => trim((string) ($_POST['fatherAge'] ?? '')) !== '' ? (int) $_POST['fatherAge'] : null,
                ':father_occupation' => trim((string) ($_POST['fatherOccupation'] ?? '')) ?: null,
                ':father_remarks' => trim((string) ($_POST['fatherRemarks'] ?? '')) ?: null,
                ':mother_name' => trim((string) ($_POST['motherName'] ?? '')) ?: null,
                ':mother_age' => trim((string) ($_POST['motherAge'] ?? '')) !== '' ? (int) $_POST['motherAge'] : null,
                ':mother_occupation' => trim((string) ($_POST['motherOccupation'] ?? '')) ?: null,
                ':mother_remarks' => trim((string) ($_POST['motherRemarks'] ?? '')) ?: null,
                ':flagged' => $isFlagged,
                ':flag_reason' => $flagReason,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);

            $applicantId = (int) $pdo->lastInsertId();

            $birthCert = save_uploaded_pdf('birthCert');
            $schoolReg = save_uploaded_pdf('schoolReg');
            $latestGrade1 = save_uploaded_pdf('latestGrade1');
            $latestGrade2 = save_uploaded_pdf('latestGrade2');

            $insertDoc = $pdo->prepare(
                'INSERT INTO spes_applicant_documents (
                    spes_applicant_id, birth_cert_path, school_reg_path, latest_grade1_path, latest_grade2_path,
                    birth_cert_name, school_reg_name, latest_grade1_name, latest_grade2_name, created_at, updated_at
                ) VALUES (
                    :spes_applicant_id, :birth_cert_path, :school_reg_path, :latest_grade1_path, :latest_grade2_path,
                    :birth_cert_name, :school_reg_name, :latest_grade1_name, :latest_grade2_name, :created_at, :updated_at
                )'
            );

            $insertDoc->execute([
                ':spes_applicant_id' => $applicantId,
                ':birth_cert_path' => $birthCert['path'],
                ':school_reg_path' => $schoolReg['path'],
                ':latest_grade1_path' => $latestGrade1['path'],
                ':latest_grade2_path' => $latestGrade2['path'],
                ':birth_cert_name' => $birthCert['name'],
                ':school_reg_name' => $schoolReg['name'],
                ':latest_grade1_name' => $latestGrade1['name'],
                ':latest_grade2_name' => $latestGrade2['name'],
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);

            send_json(201, [
                'message' => 'Applicant stored successfully under SPES Management',
                'data' => [
                    'id' => $applicantId,
                    'controlNo' => $controlNo,
                    'status' => 'Pending',
                    'source' => 'Walk-in',
                    'flagged' => $isFlagged === 1,
                    'flagReason' => $flagReason,
                ],
            ]);
        } catch (Throwable $dbError) {
            $rows = read_local_applicants();
            foreach ($rows as $row) {
                if (isset($row['email']) && strcasecmp((string) $row['email'], $email) === 0) {
                    send_json(422, ['message' => 'The email has already been taken.']);
                }
            }

            $managementRows = read_local_records(local_management_path());
            $deploymentRows = read_local_records(local_deployment_path());
            $flagDecision = duplicate_check_local($rows, $managementRows, $deploymentRows, $surnameKey, $parentKey);
            $isFlagged = $flagDecision['flagged'];
            $flagReason = $flagDecision['reason'];

            $maxId = 0;
            foreach ($rows as $row) {
                $maxId = max($maxId, (int) ($row['id'] ?? 0));
            }
            foreach ($managementRows as $row) {
                $maxId = max($maxId, (int) ($row['applicant_id'] ?? 0));
            }
            foreach ($deploymentRows as $row) {
                $maxId = max($maxId, (int) ($row['applicant_id'] ?? 0));
            }
            $applicantId = $maxId + 1;
            $controlNo = sprintf('%s-%04d', gmdate('Y'), $applicantId);

            $localRecord = [
                'id' => $applicantId,
                'control_no' => $controlNo,
                'source' => 'Walk-in',
                'status' => 'Pending',
                'archived' => 0,
                'last_name' => trim((string) ($_POST['lastName'] ?? '')),
                'first_name' => trim((string) ($_POST['firstName'] ?? '')),
                'middle_name' => trim((string) ($_POST['middleName'] ?? '')) ?: null,
                'street' => $addressParts['street'],
                'district' => $addressParts['district'],
                'city' => $addressParts['city'],
                'province' => $addressParts['province'],
                'address' => $address !== '' ? $address : null,
                'dob' => trim((string) ($_POST['dob'] ?? '')),
                'age' => trim((string) ($_POST['age'] ?? '')) !== '' ? (int) $_POST['age'] : null,
                'sex' => trim((string) ($_POST['sex'] ?? '')),
                'place_of_birth' => trim((string) ($_POST['placeOfBirth'] ?? '')) ?: null,
                'email' => $email,
                'contact_number' => trim((string) ($_POST['contactNumber'] ?? '')),
                'fb_account' => trim((string) ($_POST['fbAccount'] ?? '')) ?: null,
                'is_scholar' => trim((string) ($_POST['isScholar'] ?? '')) ?: null,
                'scholarship_name' => trim((string) ($_POST['scholarshipName'] ?? '')) ?: null,
                'is_4ps' => trim((string) ($_POST['is4ps'] ?? '')) ?: null,
                'is_pwd' => trim((string) ($_POST['isPwd'] ?? '')) ?: null,
                'elem_school' => trim((string) ($_POST['elemSchool'] ?? '')) ?: null,
                'jhs_school' => trim((string) ($_POST['jhsSchool'] ?? '')) ?: null,
                'shs_school' => trim((string) ($_POST['shsSchool'] ?? '')) ?: null,
                'shs_track' => trim((string) ($_POST['shsTrack'] ?? '')) ?: null,
                'college_school' => trim((string) ($_POST['collegeSchool'] ?? '')),
                'college_course' => trim((string) ($_POST['collegeCourse'] ?? '')),
                'father_name' => trim((string) ($_POST['fatherName'] ?? '')) ?: null,
                'father_age' => trim((string) ($_POST['fatherAge'] ?? '')) !== '' ? (int) $_POST['fatherAge'] : null,
                'father_occupation' => trim((string) ($_POST['fatherOccupation'] ?? '')) ?: null,
                'father_remarks' => trim((string) ($_POST['fatherRemarks'] ?? '')) ?: null,
                'mother_name' => trim((string) ($_POST['motherName'] ?? '')) ?: null,
                'mother_age' => trim((string) ($_POST['motherAge'] ?? '')) !== '' ? (int) $_POST['motherAge'] : null,
                'mother_occupation' => trim((string) ($_POST['motherOccupation'] ?? '')) ?: null,
                'mother_remarks' => trim((string) ($_POST['motherRemarks'] ?? '')) ?: null,
                'flagged' => $isFlagged,
                'flag_reason' => $flagReason,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            array_unshift($rows, $localRecord);
            write_local_applicants($rows);

            send_json(201, [
                'message' => 'Applicant stored successfully under SPES Management',
                'data' => [
                    'id' => $applicantId,
                    'controlNo' => $controlNo,
                    'status' => 'Pending',
                    'source' => 'Walk-in',
                    'flagged' => $isFlagged,
                    'flagReason' => $flagReason,
                    'storage' => 'local-fallback',
                ],
            ]);
        }
    }

    if ($method === 'POST' && preg_match('#^/api/pending-applicants/(\d+)/reject$#', $path, $matches)) {
        $applicantId = (int) ($matches[1] ?? 0);
        if ($applicantId <= 0) {
            send_json(422, ['message' => 'Invalid applicant id']);
        }

        try {
            $pdo = $pdo instanceof PDO ? $pdo : pdo();

            $find = $pdo->prepare('SELECT id FROM spes_applicants WHERE id = :id AND archived = 0 LIMIT 1');
            $find->execute([':id' => $applicantId]);
            if (! $find->fetch()) {
                send_json(404, ['message' => 'Pending applicant not found']);
            }

            $pdo->beginTransaction();

            $deleteDocs = $pdo->prepare('DELETE FROM spes_applicant_documents WHERE spes_applicant_id = :id');
            $deleteDocs->execute([':id' => $applicantId]);

            $deleteApplicant = $pdo->prepare('DELETE FROM spes_applicants WHERE id = :id');
            $deleteApplicant->execute([':id' => $applicantId]);

            $pdo->commit();

            send_json(200, [
                'message' => 'Applicant rejected and removed from pending list',
                'data' => [
                    'id' => $applicantId,
                    'action' => 'rejected',
                ],
            ]);
        } catch (Throwable $dbError) {
            $rows = read_local_applicants();
            $before = count($rows);
            $rows = array_values(array_filter($rows, static fn ($row) => ((int) ($row['id'] ?? 0)) !== $applicantId));

            if ($before === count($rows)) {
                send_json(404, ['message' => 'Pending applicant not found']);
            }

            write_local_applicants($rows);

            send_json(200, [
                'message' => 'Applicant rejected and removed from pending list',
                'data' => [
                    'id' => $applicantId,
                    'action' => 'rejected',
                    'storage' => 'local-fallback',
                ],
            ]);
        }
    }

    if ($method === 'POST' && preg_match('#^/api/pending-applicants/(\d+)/approve$#', $path, $matches)) {
        $applicantId = (int) ($matches[1] ?? 0);
        if ($applicantId <= 0) {
            send_json(422, ['message' => 'Invalid applicant id']);
        }

        try {
            $pdo = $pdo instanceof PDO ? $pdo : pdo();

            $find = $pdo->prepare('SELECT * FROM spes_applicants WHERE id = :id AND archived = 0 LIMIT 1');
            $find->execute([':id' => $applicantId]);
            $applicant = $find->fetch();

            if (! $applicant) {
                send_json(404, ['message' => 'Pending applicant not found']);
            }

            if (((int) ($applicant['flagged'] ?? 0)) === 1) {
                $reason = trim((string) ($applicant['flag_reason'] ?? ''));
                $message = 'Applicant is flagged due to duplicate family record';
                if ($reason !== '') {
                    $message .= ' (' . $reason . ')';
                }
                send_json(422, ['message' => $message]);
            }

            if (!valid_email((string) ($applicant['email'] ?? ''))) {
                send_json(422, ['message' => 'Applicant has invalid email and cannot be approved']);
            }

            $approvedAt = now_utc();
            $pdo->beginTransaction();

            $insertManagement = $pdo->prepare(
                'INSERT INTO spes_management_records (
                    applicant_id, control_no, source, status,
                    last_name, first_name, middle_name, street, district, city, province, address,
                    dob, age, sex, place_of_birth, email, contact_number, fb_account,
                    is_scholar, scholarship_name, is_4ps, is_pwd,
                    elem_school, jhs_school, shs_school, shs_track,
                    college_school, college_course,
                    father_name, father_age, father_occupation, father_remarks,
                    mother_name, mother_age, mother_occupation, mother_remarks,
                    approved_at, created_at, updated_at
                ) VALUES (
                    :applicant_id, :control_no, :source, :status,
                    :last_name, :first_name, :middle_name, :street, :district, :city, :province, :address,
                    :dob, :age, :sex, :place_of_birth, :email, :contact_number, :fb_account,
                    :is_scholar, :scholarship_name, :is_4ps, :is_pwd,
                    :elem_school, :jhs_school, :shs_school, :shs_track,
                    :college_school, :college_course,
                    :father_name, :father_age, :father_occupation, :father_remarks,
                    :mother_name, :mother_age, :mother_occupation, :mother_remarks,
                    :approved_at, :created_at, :updated_at
                )'
            );

            $insertManagement->execute([
                ':applicant_id' => (int) $applicant['id'],
                ':control_no' => $applicant['control_no'],
                ':source' => $applicant['source'],
                ':status' => 'Approved',
                ':last_name' => $applicant['last_name'],
                ':first_name' => $applicant['first_name'],
                ':middle_name' => $applicant['middle_name'],
                ':street' => $applicant['street'],
                ':district' => $applicant['district'],
                ':city' => $applicant['city'],
                ':province' => $applicant['province'],
                ':address' => $applicant['address'],
                ':dob' => $applicant['dob'],
                ':age' => $applicant['age'],
                ':sex' => $applicant['sex'],
                ':place_of_birth' => $applicant['place_of_birth'],
                ':email' => $applicant['email'],
                ':contact_number' => $applicant['contact_number'],
                ':fb_account' => $applicant['fb_account'],
                ':is_scholar' => $applicant['is_scholar'],
                ':scholarship_name' => $applicant['scholarship_name'],
                ':is_4ps' => $applicant['is_4ps'],
                ':is_pwd' => $applicant['is_pwd'],
                ':elem_school' => $applicant['elem_school'],
                ':jhs_school' => $applicant['jhs_school'],
                ':shs_school' => $applicant['shs_school'],
                ':shs_track' => $applicant['shs_track'],
                ':college_school' => $applicant['college_school'],
                ':college_course' => $applicant['college_course'],
                ':father_name' => $applicant['father_name'],
                ':father_age' => $applicant['father_age'],
                ':father_occupation' => $applicant['father_occupation'],
                ':father_remarks' => $applicant['father_remarks'],
                ':mother_name' => $applicant['mother_name'],
                ':mother_age' => $applicant['mother_age'],
                ':mother_occupation' => $applicant['mother_occupation'],
                ':mother_remarks' => $applicant['mother_remarks'],
                ':approved_at' => $approvedAt,
                ':created_at' => $approvedAt,
                ':updated_at' => $approvedAt,
            ]);

            $managementId = (int) $pdo->lastInsertId();
            $fullName = trim((string) $applicant['last_name'] . ', ' . (string) $applicant['first_name'] . ' ' . (string) ($applicant['middle_name'] ?? ''));

            $insertDeployment = $pdo->prepare(
                'INSERT INTO deployment_records (
                    management_record_id, applicant_id, control_no, full_name,
                    school, course, district, email, contact_number,
                    deployment_status, deployed_at, created_at, updated_at
                ) VALUES (
                    :management_record_id, :applicant_id, :control_no, :full_name,
                    :school, :course, :district, :email, :contact_number,
                    :deployment_status, :deployed_at, :created_at, :updated_at
                )'
            );

            $insertDeployment->execute([
                ':management_record_id' => $managementId,
                ':applicant_id' => (int) $applicant['id'],
                ':control_no' => $applicant['control_no'],
                ':full_name' => $fullName,
                ':school' => $applicant['college_school'],
                ':course' => $applicant['college_course'],
                ':district' => $applicant['district'],
                ':email' => $applicant['email'],
                ':contact_number' => $applicant['contact_number'],
                ':deployment_status' => 'Queued',
                ':deployed_at' => null,
                ':created_at' => $approvedAt,
                ':updated_at' => $approvedAt,
            ]);

            $deleteDocs = $pdo->prepare('DELETE FROM spes_applicant_documents WHERE spes_applicant_id = :id');
            $deleteDocs->execute([':id' => $applicantId]);

            $deletePending = $pdo->prepare('DELETE FROM spes_applicants WHERE id = :id');
            $deletePending->execute([':id' => $applicantId]);

            $pdo->commit();

            send_json(200, [
                'message' => 'Applicant approved and transferred to SPES Management + Deployment',
                'data' => [
                    'id' => $applicantId,
                    'controlNo' => $applicant['control_no'],
                    'managementRecordId' => $managementId,
                    'action' => 'approved',
                ],
            ]);
        } catch (Throwable $dbError) {
            $rows = read_local_applicants();
            $index = null;
            foreach ($rows as $k => $row) {
                if (((int) ($row['id'] ?? 0)) === $applicantId) {
                    $index = $k;
                    break;
                }
            }

            if ($index === null) {
                send_json(404, ['message' => 'Pending applicant not found']);
            }

            $record = $rows[$index];
            if (!empty($record['flagged'])) {
                $reason = trim((string) ($record['flag_reason'] ?? ''));
                $message = 'Applicant is flagged due to duplicate family record';
                if ($reason !== '') {
                    $message .= ' (' . $reason . ')';
                }
                send_json(422, ['message' => $message]);
            }

            if (!valid_email((string) ($record['email'] ?? ''))) {
                send_json(422, ['message' => 'Applicant has invalid email and cannot be approved']);
            }

            unset($rows[$index]);
            write_local_applicants($rows);

            $managementRows = read_local_records(local_management_path());
            $maxManagementId = 0;
            foreach ($managementRows as $item) {
                $maxManagementId = max($maxManagementId, (int) ($item['id'] ?? 0));
            }
            $managementId = $maxManagementId + 1;

            $approvedAt = now_utc();
            $managementRecord = [
                'id' => $managementId,
                'applicant_id' => (int) ($record['id'] ?? 0),
                'control_no' => $record['control_no'] ?? ('LOCAL-' . (string) $managementId),
                'source' => $record['source'] ?? 'Walk-in',
                'status' => 'Approved',
                'archived' => 0,
                'last_name' => $record['last_name'] ?? '',
                'first_name' => $record['first_name'] ?? '',
                'middle_name' => $record['middle_name'] ?? null,
                'street' => $record['street'] ?? null,
                'district' => $record['district'] ?? null,
                'city' => $record['city'] ?? null,
                'province' => $record['province'] ?? null,
                'address' => $record['address'] ?? null,
                'dob' => $record['dob'] ?? null,
                'age' => $record['age'] ?? null,
                'sex' => $record['sex'] ?? null,
                'place_of_birth' => $record['place_of_birth'] ?? null,
                'email' => $record['email'] ?? '',
                'contact_number' => $record['contact_number'] ?? null,
                'fb_account' => $record['fb_account'] ?? null,
                'is_scholar' => $record['is_scholar'] ?? null,
                'scholarship_name' => $record['scholarship_name'] ?? null,
                'is_4ps' => $record['is_4ps'] ?? null,
                'is_pwd' => $record['is_pwd'] ?? null,
                'elem_school' => $record['elem_school'] ?? null,
                'jhs_school' => $record['jhs_school'] ?? null,
                'shs_school' => $record['shs_school'] ?? null,
                'shs_track' => $record['shs_track'] ?? null,
                'college_school' => $record['college_school'] ?? null,
                'college_course' => $record['college_course'] ?? null,
                'father_name' => $record['father_name'] ?? null,
                'father_age' => $record['father_age'] ?? null,
                'father_occupation' => $record['father_occupation'] ?? null,
                'father_remarks' => $record['father_remarks'] ?? null,
                'mother_name' => $record['mother_name'] ?? null,
                'mother_age' => $record['mother_age'] ?? null,
                'mother_occupation' => $record['mother_occupation'] ?? null,
                'mother_remarks' => $record['mother_remarks'] ?? null,
                'approved_at' => $approvedAt,
                'created_at' => $approvedAt,
                'updated_at' => $approvedAt,
            ];
            array_unshift($managementRows, $managementRecord);
            write_local_records(local_management_path(), $managementRows);

            $deploymentRows = read_local_records(local_deployment_path());
            $maxDeploymentId = 0;
            foreach ($deploymentRows as $item) {
                $maxDeploymentId = max($maxDeploymentId, (int) ($item['id'] ?? 0));
            }

            $fullName = trim((string) ($record['last_name'] ?? '') . ', ' . (string) ($record['first_name'] ?? '') . ' ' . (string) ($record['middle_name'] ?? ''));
            $deploymentRecord = [
                'id' => $maxDeploymentId + 1,
                'management_record_id' => $managementId,
                'applicant_id' => (int) ($record['id'] ?? 0),
                'control_no' => $record['control_no'] ?? ('LOCAL-' . (string) $managementId),
                'archived' => 0,
                'full_name' => $fullName,
                'school' => $record['college_school'] ?? null,
                'course' => $record['college_course'] ?? null,
                'district' => $record['district'] ?? null,
                'email' => $record['email'] ?? '',
                'contact_number' => $record['contact_number'] ?? null,
                'deployment_status' => 'Queued',
                'deployed_at' => null,
                'created_at' => $approvedAt,
                'updated_at' => $approvedAt,
            ];
            array_unshift($deploymentRows, $deploymentRecord);
            write_local_records(local_deployment_path(), $deploymentRows);

            send_json(200, [
                'message' => 'Applicant approved and transferred to SPES Management + Deployment',
                'data' => [
                    'id' => (int) ($record['id'] ?? 0),
                    'controlNo' => $record['control_no'] ?? ('LOCAL-' . (string) $managementId),
                    'managementRecordId' => $managementId,
                    'action' => 'approved',
                    'storage' => 'local-fallback',
                ],
            ]);
        }
    }

    if ($method === 'POST' && $path === '/api/spes-records/archive') {
        $body = parse_json_body();
        $applicantId = (int) ($body['applicant_id'] ?? 0);
        $controlNo = trim((string) ($body['control_no'] ?? ''));
        $email = trim((string) ($body['email'] ?? ''));

        $now = now_utc();

        try {
            $pdo = $pdo instanceof PDO ? $pdo : pdo();

            if ($applicantId <= 0 && $controlNo === '' && $email === '') {
                send_json(422, ['message' => 'Provide applicant_id, control_no, or email']);
            }

            if ($applicantId <= 0) {
                $lookup = null;
                if ($controlNo !== '') {
                    $stmt = $pdo->prepare('SELECT applicant_id FROM spes_management_records WHERE control_no = :control_no LIMIT 1');
                    $stmt->execute([':control_no' => $controlNo]);
                    $lookup = $stmt->fetch();
                }

                if (!$lookup && $email !== '') {
                    $stmt = $pdo->prepare('SELECT applicant_id FROM spes_management_records WHERE email = :email LIMIT 1');
                    $stmt->execute([':email' => $email]);
                    $lookup = $stmt->fetch();
                }

                if (!$lookup && $email !== '') {
                    $stmt = $pdo->prepare('SELECT id AS applicant_id FROM spes_applicants WHERE email = :email LIMIT 1');
                    $stmt->execute([':email' => $email]);
                    $lookup = $stmt->fetch();
                }

                if (!$lookup && $controlNo !== '') {
                    $stmt = $pdo->prepare('SELECT id AS applicant_id FROM spes_applicants WHERE control_no = :control_no LIMIT 1');
                    $stmt->execute([':control_no' => $controlNo]);
                    $lookup = $stmt->fetch();
                }

                $applicantId = (int) ($lookup['applicant_id'] ?? 0);
            }

            if ($applicantId <= 0) {
                send_json(404, ['message' => 'Record not found']);
            }

            $affected = mysql_archive_by_applicant($pdo, $applicantId, $now);
            if (($affected['pending'] + $affected['management'] + $affected['deployment']) === 0) {
                send_json(404, ['message' => 'Record not found or already archived']);
            }

            send_json(200, [
                'message' => 'Record archived across SPES modules',
                'data' => [
                    'applicant_id' => $applicantId,
                    'updated' => $affected,
                ],
            ]);
        } catch (Throwable $dbError) {
            if ($applicantId <= 0) {
                send_json(404, ['message' => 'Record not found']);
            }

            $affected = local_archive_by_applicant($applicantId, $now);
            if (($affected['pending'] + $affected['management'] + $affected['deployment']) === 0) {
                send_json(404, ['message' => 'Record not found or already archived']);
            }

            send_json(200, [
                'message' => 'Record archived across SPES modules',
                'data' => [
                    'applicant_id' => $applicantId,
                    'updated' => $affected,
                    'storage' => 'local-fallback',
                ],
            ]);
        }
    }

    if ($method === 'POST' && preg_match('#^/api/spes-records/(\d+)/archive$#', $path, $matches)) {
        $applicantId = (int) ($matches[1] ?? 0);
        if ($applicantId <= 0) {
            send_json(422, ['message' => 'Invalid applicant id']);
        }

        $now = now_utc();

        try {
            $pdo = $pdo instanceof PDO ? $pdo : pdo();
            $affected = mysql_archive_by_applicant($pdo, $applicantId, $now);

            if (($affected['pending'] + $affected['management'] + $affected['deployment']) === 0) {
                send_json(404, ['message' => 'Record not found or already archived']);
            }

            send_json(200, [
                'message' => 'Record archived across SPES modules',
                'data' => [
                    'applicant_id' => $applicantId,
                    'updated' => $affected,
                ],
            ]);
        } catch (Throwable $dbError) {
            $affected = local_archive_by_applicant($applicantId, $now);
            if (($affected['pending'] + $affected['management'] + $affected['deployment']) === 0) {
                send_json(404, ['message' => 'Record not found or already archived']);
            }

            send_json(200, [
                'message' => 'Record archived across SPES modules',
                'data' => [
                    'applicant_id' => $applicantId,
                    'updated' => $affected,
                    'storage' => 'local-fallback',
                ],
            ]);
        }
    }

    if ($method === 'GET' && $path === '/api/spes-management/applicants') {
        $status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
        $statusNormalized = normalize_status_value($status);
        $fields = isset($_GET['fields']) ? strtolower(trim((string) $_GET['fields'])) : 'full';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(1000, (int) ($_GET['per_page'] ?? 20)));

        try {
            $pdo = $pdo instanceof PDO ? $pdo : pdo();

            // Keep summary mode focused on the pending queue (used by New SPES table).
            if ($fields === 'summary') {
                $where = '';
                $params = [];
                $where = ' WHERE archived = 0';
                if ($statusNormalized !== '') {
                    $where .= ' AND LOWER(status) = :status';
                    $params[':status'] = $statusNormalized;
                }

                $countStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM spes_applicants' . $where);
                $countStmt->execute($params);
                $total = (int) ($countStmt->fetch()['total'] ?? 0);

                $offset = ($page - 1) * $perPage;
                $dataSql = 'SELECT id, control_no, source, status, last_name, first_name, middle_name, district, age, sex, email, college_school, college_course, flagged, flag_reason, created_at FROM spes_applicants' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset';
                $dataStmt = $pdo->prepare($dataSql);
                foreach ($params as $k => $v) {
                    $dataStmt->bindValue($k, $v, PDO::PARAM_STR);
                }
                $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
                $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $dataStmt->execute();
                $rows = $dataStmt->fetchAll();

                $lastPage = (int) max(1, ceil($total / max(1, $perPage)));
                send_json(200, [
                    'current_page' => $page,
                    'data' => $rows,
                    'first_page_url' => 'http://127.0.0.1:8000/api/spes-management/applicants?page=1',
                    'from' => $total > 0 ? $offset + 1 : null,
                    'per_page' => $perPage,
                    'to' => $total > 0 ? min($offset + $perPage, $total) : null,
                    'total' => $total,
                    'last_page' => $lastPage,
                    'last_page_url' => 'http://127.0.0.1:8000/api/spes-management/applicants?page=' . $lastPage,
                    'next_page_url' => ($offset + $perPage) < $total ? 'http://127.0.0.1:8000/api/spes-management/applicants?page=' . ($page + 1) : null,
                    'prev_page_url' => $page > 1 ? 'http://127.0.0.1:8000/api/spes-management/applicants?page=' . ($page - 1) : null,
                    'path' => 'http://127.0.0.1:8000/api/spes-management/applicants',
                    'links' => [
                        [
                            'url' => $page > 1 ? 'http://127.0.0.1:8000/api/spes-management/applicants?page=' . ($page - 1) : null,
                            'label' => '&laquo; Previous',
                            'page' => $page > 1 ? ($page - 1) : null,
                            'active' => false,
                        ],
                        [
                            'url' => 'http://127.0.0.1:8000/api/spes-management/applicants?page=' . $page,
                            'label' => (string) $page,
                            'page' => $page,
                            'active' => true,
                        ],
                        [
                            'url' => ($offset + $perPage) < $total ? 'http://127.0.0.1:8000/api/spes-management/applicants?page=' . ($page + 1) : null,
                            'label' => 'Next &raquo;',
                            'page' => ($offset + $perPage) < $total ? ($page + 1) : null,
                            'active' => false,
                        ],
                    ],
                ]);
            }

            // Full mode returns both pending queue + approved management records.
            $appStmt = $pdo->query('SELECT * FROM spes_applicants WHERE archived = 0 ORDER BY id DESC');
            $pendingRows = $appStmt->fetchAll();

            $mgmtStmt = $pdo->query('SELECT * FROM spes_management_records WHERE archived = 0 ORDER BY id DESC');
            $managementRows = $mgmtStmt->fetchAll();

            $rows = [];
            foreach ($pendingRows as $row) {
                $rows[] = $row;
            }
            foreach ($managementRows as $row) {
                $rows[] = map_management_to_applicant_row($row);
            }

            if ($statusNormalized !== '') {
                $rows = array_values(array_filter($rows, static fn ($r) => normalize_status_value($r['status'] ?? '') === $statusNormalized));
            }

            usort($rows, static function ($a, $b) {
                $ta = strtotime((string) ($a['created_at'] ?? '')) ?: 0;
                $tb = strtotime((string) ($b['created_at'] ?? '')) ?: 0;
                if ($tb !== $ta) {
                    return $tb <=> $ta;
                }

                return ((int) ($b['id'] ?? 0)) <=> ((int) ($a['id'] ?? 0));
            });

            send_json(200, applicants_paginated_response($rows, $page, $perPage));
        } catch (Throwable $dbError) {
            $rows = array_values(array_filter(read_local_applicants(), static fn ($r) => ((int) ($r['archived'] ?? 0)) === 0));

            if ($fields === 'summary') {
                if ($statusNormalized !== '') {
                    $rows = array_values(array_filter($rows, static fn ($r) => normalize_status_value($r['status'] ?? '') === $statusNormalized));
                }
                usort($rows, static fn ($a, $b) => ((int) ($b['id'] ?? 0)) <=> ((int) ($a['id'] ?? 0)));
                $rows = array_map(static function ($r) {
                    return [
                        'id' => $r['id'] ?? null,
                        'control_no' => $r['control_no'] ?? null,
                        'source' => $r['source'] ?? null,
                        'status' => $r['status'] ?? null,
                        'last_name' => $r['last_name'] ?? null,
                        'first_name' => $r['first_name'] ?? null,
                        'middle_name' => $r['middle_name'] ?? null,
                        'district' => $r['district'] ?? null,
                        'age' => $r['age'] ?? null,
                        'sex' => $r['sex'] ?? null,
                        'email' => $r['email'] ?? null,
                        'college_school' => $r['college_school'] ?? null,
                        'college_course' => $r['college_course'] ?? null,
                        'flagged' => !empty($r['flagged']) ? 1 : 0,
                        'flag_reason' => $r['flag_reason'] ?? null,
                        'created_at' => $r['created_at'] ?? null,
                    ];
                }, $rows);

                send_json(200, applicants_paginated_response($rows, $page, $perPage));
            }

            $managementRows = array_values(array_filter(read_local_records(local_management_path()), static fn ($r) => ((int) ($r['archived'] ?? 0)) === 0));
            $composed = [];
            foreach ($rows as $row) {
                $composed[] = $row;
            }
            foreach ($managementRows as $row) {
                $composed[] = map_management_to_applicant_row($row);
            }

            if ($statusNormalized !== '') {
                $composed = array_values(array_filter($composed, static fn ($r) => normalize_status_value($r['status'] ?? '') === $statusNormalized));
            }

            usort($composed, static function ($a, $b) {
                $ta = strtotime((string) ($a['created_at'] ?? '')) ?: 0;
                $tb = strtotime((string) ($b['created_at'] ?? '')) ?: 0;
                if ($tb !== $ta) {
                    return $tb <=> $ta;
                }

                return ((int) ($b['id'] ?? 0)) <=> ((int) ($a['id'] ?? 0));
            });

            send_json(200, applicants_paginated_response($composed, $page, $perPage));
        }
    }

    if ($method === 'GET' && $path === '/api/spes-management/records') {
        $status = isset($_GET['status']) ? trim((string) $_GET['status']) : 'approved';
        $statusNormalized = normalize_status_value($status);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(200, (int) ($_GET['per_page'] ?? 20)));

        try {
            $pdo = $pdo instanceof PDO ? $pdo : pdo();

            $where = '';
            $params = [];
            $where = ' WHERE archived = 0';
            if ($statusNormalized !== '' && $statusNormalized !== 'all') {
                $where .= ' AND LOWER(status) = :status';
                $params[':status'] = $statusNormalized;
            }

            $countStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM spes_management_records' . $where);
            $countStmt->execute($params);
            $total = (int) (($countStmt->fetch()['total'] ?? 0));
            $offset = ($page - 1) * $perPage;

            $stmt = $pdo->prepare('SELECT * FROM spes_management_records' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset');
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();

            $lastPage = (int) max(1, ceil($total / max(1, $perPage)));
            send_json(200, [
                'current_page' => $page,
                'data' => $rows,
                'from' => $total > 0 ? $offset + 1 : null,
                'per_page' => $perPage,
                'to' => $total > 0 ? min($offset + $perPage, $total) : null,
                'total' => $total,
                'last_page' => $lastPage,
            ]);
        } catch (Throwable $dbError) {
            $rows = array_values(array_filter(read_local_records(local_management_path()), static fn ($r) => ((int) ($r['archived'] ?? 0)) === 0));
            if ($statusNormalized !== '' && $statusNormalized !== 'all') {
                $rows = array_values(array_filter($rows, static fn ($r) => normalize_status_value($r['status'] ?? '') === $statusNormalized));
            }
            usort($rows, static fn ($a, $b) => ((int) ($b['id'] ?? 0)) <=> ((int) ($a['id'] ?? 0)));
            send_json(200, applicants_paginated_response($rows, $page, $perPage));
        }
    }

    if ($method === 'GET' && $path === '/api/deployment/records') {
        $status = isset($_GET['status']) ? trim((string) $_GET['status']) : 'approved';
        $statusNormalized = normalize_status_value($status);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(200, (int) ($_GET['per_page'] ?? 20)));

        try {
            $pdo = $pdo instanceof PDO ? $pdo : pdo();

            $where = ' WHERE archived = 0';
            $params = [];
            if ($statusNormalized !== '' && $statusNormalized !== 'all') {
                if ($statusNormalized === 'approved') {
                    $where .= ' AND LOWER(deployment_status) IN ("queued", "deployed")';
                } else {
                    $where .= ' AND LOWER(deployment_status) = :status';
                    $params[':status'] = $statusNormalized;
                }
            }

            $countStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM deployment_records' . $where);
            $countStmt->execute($params);
            $total = (int) (($countStmt->fetch()['total'] ?? 0));
            $offset = ($page - 1) * $perPage;

            $stmt = $pdo->prepare('SELECT * FROM deployment_records' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset');
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();

            foreach ($rows as &$row) {
                if (!isset($row['status'])) {
                    $row['status'] = 'Approved';
                }
            }
            unset($row);

            $lastPage = (int) max(1, ceil($total / max(1, $perPage)));
            send_json(200, [
                'current_page' => $page,
                'data' => $rows,
                'from' => $total > 0 ? $offset + 1 : null,
                'per_page' => $perPage,
                'to' => $total > 0 ? min($offset + $perPage, $total) : null,
                'total' => $total,
                'last_page' => $lastPage,
            ]);
        } catch (Throwable $dbError) {
            $rows = array_values(array_filter(read_local_records(local_deployment_path()), static fn ($r) => ((int) ($r['archived'] ?? 0)) === 0));
            if ($statusNormalized !== '' && $statusNormalized !== 'all') {
                if ($statusNormalized === 'approved') {
                    $rows = array_values(array_filter($rows, static function ($r) {
                        $deploymentStatus = normalize_status_value($r['deployment_status'] ?? '');
                        return $deploymentStatus === 'queued' || $deploymentStatus === 'deployed';
                    }));
                } else {
                    $rows = array_values(array_filter($rows, static fn ($r) => normalize_status_value($r['deployment_status'] ?? '') === $statusNormalized));
                }
            }

            foreach ($rows as &$row) {
                if (!isset($row['status'])) {
                    $row['status'] = 'Approved';
                }
            }
            unset($row);

            usort($rows, static fn ($a, $b) => ((int) ($b['id'] ?? 0)) <=> ((int) ($a['id'] ?? 0)));
            send_json(200, applicants_paginated_response($rows, $page, $perPage));
        }
    }

    send_json(404, ['message' => 'Endpoint not found']);
} catch (Throwable $e) {
    send_json(500, [
        'message' => $e->getMessage() !== '' ? $e->getMessage() : 'Server error',
        'error' => $e->getMessage(),
    ]);
}
