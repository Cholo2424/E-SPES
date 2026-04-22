<?php

declare(strict_types=1);

if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
}

const ADMIN_EMAIL = 'admin@ccc.edu.ph';
const EMPLOYEE_EMAIL = 'employee@ccc.edu.ph';

function send_json(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function cors_headers(): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
}

function parse_json_body(): array
{
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : [];
}

function now_utc(): string
{
    return gmdate('Y-m-d H:i:s');
}

function pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = mysql_config();
    $timeoutSeconds = max(1, (int) ($config['timeout'] ?? 2));
    $cooldownSeconds = max(1, (int) ($config['retry_cooldown'] ?? 15));

    if (is_db_temporarily_unavailable()) {
        throw new RuntimeException('Database temporarily unavailable');
    }

    try {
        $serverDsn = 'mysql:host=' . $config['host'] . ';port=' . $config['port'] . ';charset=' . $config['charset'];
        $server = new PDO($serverDsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => $timeoutSeconds,
        ]);

        $server->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $config['database']) . '` CHARACTER SET ' . $config['charset'] . ' COLLATE utf8mb4_unicode_ci');

        $dsn = 'mysql:host=' . $config['host'] . ';port=' . $config['port'] . ';dbname=' . $config['database'] . ';charset=' . $config['charset'];
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => $timeoutSeconds,
        ]);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        bootstrap_schema($pdo);
        clear_db_unavailable_marker();
    } catch (Throwable $e) {
        mark_db_temporarily_unavailable($cooldownSeconds);
        throw $e;
    }

    return $pdo;
}

function bootstrap_schema(PDO $pdo): void
{
    $pdo->exec('SET NAMES utf8mb4');

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS students (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            student_no VARCHAR(100) NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            INDEX idx_students_email (email)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS password_reset_codes (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            verification_code VARCHAR(20) NULL,
            expires_at DATETIME NULL,
            used_at DATETIME NULL,
            reset_token VARCHAR(255) NULL,
            token_expires_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            INDEX idx_reset_email (email)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS spes_applicants (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            control_no VARCHAR(50) NOT NULL,
            source VARCHAR(50) NOT NULL DEFAULT "Walk-in",
            status VARCHAR(50) NOT NULL DEFAULT "Pending",
            last_name VARCHAR(255) NOT NULL,
            first_name VARCHAR(255) NOT NULL,
            middle_name VARCHAR(255) NULL,
            street VARCHAR(255) NULL,
            district VARCHAR(255) NULL,
            city VARCHAR(255) NULL,
            province VARCHAR(255) NULL,
            address TEXT NULL,
            dob DATE NULL,
            age INT NULL,
            sex VARCHAR(20) NULL,
            place_of_birth VARCHAR(255) NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            contact_number VARCHAR(50) NULL,
            fb_account VARCHAR(255) NULL,
            is_scholar VARCHAR(20) NULL,
            scholarship_name VARCHAR(255) NULL,
            is_4ps VARCHAR(20) NULL,
            is_pwd VARCHAR(20) NULL,
            elem_school VARCHAR(255) NULL,
            jhs_school VARCHAR(255) NULL,
            shs_school VARCHAR(255) NULL,
            shs_track VARCHAR(255) NULL,
            college_school VARCHAR(255) NULL,
            college_course VARCHAR(255) NULL,
            father_name VARCHAR(255) NULL,
            father_age INT NULL,
            father_occupation VARCHAR(255) NULL,
            father_remarks VARCHAR(255) NULL,
            mother_name VARCHAR(255) NULL,
            mother_age INT NULL,
            mother_occupation VARCHAR(255) NULL,
            mother_remarks VARCHAR(255) NULL,
            flagged TINYINT(1) NOT NULL DEFAULT 0,
            flag_reason VARCHAR(255) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            INDEX idx_applicant_status (status),
            INDEX idx_applicant_control_no (control_no)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS spes_applicant_documents (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            spes_applicant_id BIGINT UNSIGNED NOT NULL,
            birth_cert_path TEXT NULL,
            school_reg_path TEXT NULL,
            latest_grade1_path TEXT NULL,
            latest_grade2_path TEXT NULL,
            birth_cert_name VARCHAR(255) NULL,
            school_reg_name VARCHAR(255) NULL,
            latest_grade1_name VARCHAR(255) NULL,
            latest_grade2_name VARCHAR(255) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            INDEX idx_documents_applicant (spes_applicant_id),
            CONSTRAINT fk_documents_applicant FOREIGN KEY (spes_applicant_id) REFERENCES spes_applicants(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS spes_management_records (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            applicant_id BIGINT UNSIGNED NOT NULL,
            control_no VARCHAR(50) NOT NULL,
            source VARCHAR(50) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT "Approved",
            last_name VARCHAR(255) NOT NULL,
            first_name VARCHAR(255) NOT NULL,
            middle_name VARCHAR(255) NULL,
            street VARCHAR(255) NULL,
            district VARCHAR(255) NULL,
            city VARCHAR(255) NULL,
            province VARCHAR(255) NULL,
            address TEXT NULL,
            dob DATE NULL,
            age INT NULL,
            sex VARCHAR(20) NULL,
            place_of_birth VARCHAR(255) NULL,
            email VARCHAR(255) NOT NULL,
            contact_number VARCHAR(50) NULL,
            fb_account VARCHAR(255) NULL,
            is_scholar VARCHAR(20) NULL,
            scholarship_name VARCHAR(255) NULL,
            is_4ps VARCHAR(20) NULL,
            is_pwd VARCHAR(20) NULL,
            elem_school VARCHAR(255) NULL,
            jhs_school VARCHAR(255) NULL,
            shs_school VARCHAR(255) NULL,
            shs_track VARCHAR(255) NULL,
            college_school VARCHAR(255) NULL,
            college_course VARCHAR(255) NULL,
            father_name VARCHAR(255) NULL,
            father_age INT NULL,
            father_occupation VARCHAR(255) NULL,
            father_remarks VARCHAR(255) NULL,
            mother_name VARCHAR(255) NULL,
            mother_age INT NULL,
            mother_occupation VARCHAR(255) NULL,
            mother_remarks VARCHAR(255) NULL,
            approved_at DATETIME NOT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            INDEX idx_management_status (status),
            INDEX idx_management_control_no (control_no),
            UNIQUE KEY uq_management_applicant (applicant_id)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS deployment_records (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            management_record_id BIGINT UNSIGNED NOT NULL,
            applicant_id BIGINT UNSIGNED NOT NULL,
            control_no VARCHAR(50) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            school VARCHAR(255) NULL,
            course VARCHAR(255) NULL,
            district VARCHAR(255) NULL,
            email VARCHAR(255) NOT NULL,
            contact_number VARCHAR(50) NULL,
            deployment_status VARCHAR(50) NOT NULL DEFAULT "Queued",
            deployed_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            INDEX idx_deployment_status (deployment_status),
            INDEX idx_deployment_control_no (control_no),
            UNIQUE KEY uq_deployment_management (management_record_id)
        )'
    );

    ensure_table_column($pdo, 'spes_applicants', 'flagged', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_table_column($pdo, 'spes_applicants', 'flag_reason', 'VARCHAR(255) NULL');
    ensure_table_column($pdo, 'spes_applicants', 'archived', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_table_column($pdo, 'spes_management_records', 'archived', 'TINYINT(1) NOT NULL DEFAULT 0');
    ensure_table_column($pdo, 'deployment_records', 'archived', 'TINYINT(1) NOT NULL DEFAULT 0');

    seed_default_users($pdo);
    seed_default_students($pdo);
}

function ensure_table_column(PDO $pdo, string $table, string $column, string $definition): void
{
    $sql = 'SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':table' => $table, ':column' => $column]);
    $exists = (int) (($stmt->fetch()['total'] ?? 0));
    if ($exists > 0) {
        return;
    }

    $pdo->exec('ALTER TABLE `' . str_replace('`', '``', $table) . '` ADD COLUMN `' . str_replace('`', '``', $column) . '` ' . $definition);
}

function seed_default_users(PDO $pdo): void
{
    $defaults = [
        ['System Admin', 'admin@ccc.edu.ph', 'Admin@12345'],
        ['SPES Employee', 'employee@ccc.edu.ph', 'Employee@12345'],
        ['SPES Student', 'student@ccc.edu.ph', 'Student@12345'],
        ['SPES Student Gmail', 'khenvergara29@gmail.com', 'Student@12345'],
    ];

    $select = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $insert = $pdo->prepare(
        'INSERT INTO users (name, email, password, created_at, updated_at)
         VALUES (:name, :email, :password, :created_at, :updated_at)'
    );

    foreach ($defaults as [$name, $email, $password]) {
        $select->execute([':email' => $email]);
        if ($select->fetch()) {
            continue;
        }

        $now = now_utc();
        $insert->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => password_hash($password, PASSWORD_BCRYPT),
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
    }
}

function seed_default_students(PDO $pdo): void
{
    $defaults = [
        ['2026-0001', 'SPES Student', 'student@ccc.edu.ph', 'Student@12345'],
        ['2026-0002', 'SPES Student Gmail', 'khenvergara29@gmail.com', 'Student@12345'],
    ];

    $select = $pdo->prepare('SELECT id FROM students WHERE email = :email LIMIT 1');
    $insert = $pdo->prepare(
        'INSERT INTO students (student_no, name, email, password, created_at, updated_at)
         VALUES (:student_no, :name, :email, :password, :created_at, :updated_at)'
    );

    foreach ($defaults as [$studentNo, $name, $email, $password]) {
        $select->execute([':email' => $email]);
        if ($select->fetch()) {
            continue;
        }

        $now = now_utc();
        $insert->execute([
            ':student_no' => $studentNo,
            ':name' => $name,
            ':email' => $email,
            ':password' => password_hash($password, PASSWORD_BCRYPT),
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
    }
}

function required_field(array $data, string $key, string $label): ?string
{
    $value = isset($data[$key]) ? trim((string) $data[$key]) : '';
    if ($value === '') {
        return $label . ' is required';
    }

    return null;
}

function valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function parse_address(?string $address): array
{
    if (!$address) {
        return ['street' => null, 'district' => null, 'city' => null, 'province' => null];
    }

    $parts = array_values(array_filter(array_map('trim', explode(',', $address)), static fn($p) => $p !== ''));
    $district = $parts[1] ?? null;
    if (is_string($district)) {
        $district = preg_replace('/^District\s+/i', '', $district) ?: $district;
    }

    return [
        'street' => $parts[0] ?? null,
        'district' => $district,
        'city' => $parts[2] ?? null,
        'province' => $parts[3] ?? null,
    ];
}

function generate_control_no(PDO $pdo): string
{
    $max = (int) $pdo->query('SELECT COALESCE(MAX(id), 0) AS max_id FROM spes_applicants')->fetch()['max_id'];

    return sprintf('%s-%04d', gmdate('Y'), $max + 1);
}

function save_uploaded_pdf(string $field): array
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return ['path' => null, 'name' => null];
    }

    $file = $_FILES[$field];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['path' => null, 'name' => null];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $original = (string) ($file['name'] ?? '');
    if ($tmp === '' || $original === '') {
        return ['path' => null, 'name' => null];
    }

    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        return ['path' => null, 'name' => null];
    }

    $targetDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'spes-documents';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $filename = uniqid('doc_', true) . '.pdf';
    $target = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmp, $target)) {
        return ['path' => null, 'name' => null];
    }

    return [
        'path' => 'uploads/spes-documents/' . $filename,
        'name' => $original,
    ];
}

function write_mail_log(string $email, string $subject, string $body): void
{
    $line = '[' . now_utc() . '] To: ' . $email . PHP_EOL . 'Subject: ' . $subject . PHP_EOL . $body . PHP_EOL . str_repeat('-', 60) . PHP_EOL;
    file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'mail.log', $line, FILE_APPEND);
}

function verification_expiry_label(string $expiresAt): string
{
    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $expiresAt, new DateTimeZone('Asia/Manila'));
    if (!$dt) {
        return 'in a few minutes (Philippine Time)';
    }

    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
    $minutes = (int) ceil(($dt->getTimestamp() - $now->getTimestamp()) / 60);
    if ($minutes < 1) {
        $minutes = 1;
    }

    return 'in about ' . $minutes . ' minute' . ($minutes === 1 ? '' : 's') . ' (Philippine Time)';
}

function verification_email_html(string $code, string $expiresAt): string
{
    $expiresLabel = verification_expiry_label($expiresAt);

        return '<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Password Reset Verification</title>
</head>
<body style="margin:0;padding:0;background:#eceef3;font-family:Arial,sans-serif;color:#222;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eceef3;padding:26px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="420" cellspacing="0" cellpadding="0" style="max-width:420px;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #d8dbe2;">
                    <tr>
                        <td style="background:#242242;color:#fff;padding:14px 16px;">
                            <div style="font-size:10px;letter-spacing:.4px;opacity:.85;">SPES BENEFICIARIES MANAGEMENT INFORMATION SYSTEM</div>
                            <div style="margin-top:6px;font-size:20px;font-weight:700;line-height:1.2;">Password Reset Verification</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 16px 14px;">
                            <p style="margin:0 0 14px;font-size:14px;line-height:1.5;color:#394150;">Use the verification code below to continue your password reset request.</p>
                            <div style="border:2px dashed #9aa7ff;border-radius:10px;padding:14px 8px;text-align:center;font-size:32px;font-weight:700;letter-spacing:10px;color:#242242;background:#f7f9ff;">' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</div>
                            <p style="margin:14px 0 0;font-size:14px;line-height:1.45;color:#2c3340;">This code expires <strong>' . htmlspecialchars($expiresLabel, ENT_QUOTES, 'UTF-8') . '.</strong></p>
                            <p style="margin:12px 0 0;font-size:13px;line-height:1.5;color:#6a7384;">If you did not request this, you can ignore this email. For your safety, do not share this code with anyone.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:11px 16px;border-top:1px solid #e1e4ea;font-size:11px;color:#6f7887;">City College of Calamba · Special Program for the Employment of Students</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * @return array{host:string,port:int,username:string,password:string,encryption:string,from_address:string,from_name:string,timeout:int,smtp_auth:bool}
 */
function mail_config(): array
{
        $env = load_env(__DIR__ . DIRECTORY_SEPARATOR . '.env');

        return [
                'host' => (string) ($env['MAIL_HOST'] ?? 'smtp.gmail.com'),
                'port' => max(1, (int) ($env['MAIL_PORT'] ?? 587)),
                'username' => (string) ($env['MAIL_USERNAME'] ?? ''),
                'password' => (string) ($env['MAIL_PASSWORD'] ?? ''),
                'encryption' => strtolower((string) ($env['MAIL_ENCRYPTION'] ?? 'tls')),
                'from_address' => (string) ($env['MAIL_FROM_ADDRESS'] ?? 'no-reply@ccc.edu.ph'),
                'from_name' => (string) ($env['MAIL_FROM_NAME'] ?? 'SPES MIS'),
                'timeout' => max(1, (int) ($env['MAIL_TIMEOUT'] ?? 15)),
                'smtp_auth' => filter_var((string) ($env['MAIL_SMTP_AUTH'] ?? 'true'), FILTER_VALIDATE_BOOLEAN),
        ];
}

function send_verification_code_email(string $email, string $code, string $expiresAt): void
{
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                throw new RuntimeException('PHPMailer is not installed. Run composer install in plain-php-backend.');
        }

        $cfg = mail_config();
        if ($cfg['username'] === '' || $cfg['password'] === '') {
                throw new RuntimeException('MAIL_USERNAME and MAIL_PASSWORD must be set in plain-php-backend/.env');
        }

        $subject = 'Password Reset Verification Code';
        $html = verification_email_html($code, $expiresAt);
        $text = 'Your verification code is ' . $code . '. It expires at ' . verification_expiry_label($expiresAt) . '.';

        $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = $cfg['host'];
        $mailer->Port = $cfg['port'];
        $mailer->SMTPAuth = $cfg['smtp_auth'];
        $mailer->Username = $cfg['username'];
        $mailer->Password = $cfg['password'];
        $mailer->Timeout = $cfg['timeout'];
        $mailer->CharSet = 'UTF-8';

        if ($cfg['encryption'] === 'ssl') {
                $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($cfg['encryption'] === 'tls') {
                $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mailer->setFrom($cfg['from_address'], $cfg['from_name']);
        $mailer->addAddress($email);
        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body = $html;
        $mailer->AltBody = $text;
        $mailer->send();
}

function db_unavailable_marker_path(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . '_db_unavailable_until.txt';
}

function is_db_temporarily_unavailable(): bool
{
    $path = db_unavailable_marker_path();
    if (!file_exists($path)) {
        return false;
    }

    $raw = trim((string) @file_get_contents($path));
    if ($raw === '' || !ctype_digit($raw)) {
        @unlink($path);
        return false;
    }

    $until = (int) $raw;
    if ($until <= time()) {
        @unlink($path);
        return false;
    }

    return true;
}

function mark_db_temporarily_unavailable(int $seconds): void
{
    $until = time() + max(1, $seconds);
    @file_put_contents(db_unavailable_marker_path(), (string) $until);
}

function clear_db_unavailable_marker(): void
{
    $path = db_unavailable_marker_path();
    if (file_exists($path)) {
        @unlink($path);
    }
}

/**
 * @return array{host:string,port:string,database:string,username:string,password:string,charset:string,timeout:int,retry_cooldown:int}
 */
function mysql_config(): array
{
    $env = load_env(__DIR__ . DIRECTORY_SEPARATOR . '.env');

    return [
        'host' => (string) ($env['DB_HOST'] ?? '127.0.0.1'),
        'port' => (string) ($env['DB_PORT'] ?? '3306'),
        'database' => (string) ($env['DB_DATABASE'] ?? 'espes_plain_php'),
        'username' => (string) ($env['DB_USERNAME'] ?? 'root'),
        'password' => (string) ($env['DB_PASSWORD'] ?? ''),
        'charset' => (string) ($env['DB_CHARSET'] ?? 'utf8mb4'),
        'timeout' => max(1, (int) ($env['DB_CONNECT_TIMEOUT'] ?? 2)),
        'retry_cooldown' => max(1, (int) ($env['DB_RETRY_COOLDOWN'] ?? 15)),
    ];
}

function load_env(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return [];
    }

    $values = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        $parts = explode('=', $trimmed, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);
        $value = trim($value, "\"'");

        if ($key !== '') {
            $values[$key] = $value;
        }
    }

    return $values;
}
