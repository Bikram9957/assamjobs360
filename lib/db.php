<?php
declare(strict_types=1);

require_once __DIR__ . '/db_guard.php';

function db(): mysqli {

    static $conn = null;

    if ($conn instanceof mysqli) return $conn;

    $cfg = require __DIR__ . '/../config/config.php';

    $conn = new mysqli($cfg['DB_HOST'], $cfg['DB_USER'], $cfg['DB_PASS'], $cfg['DB_NAME']);

    if ($conn->connect_errno) {
        http_response_code(500);

        // If the request is expecting JSON (API/AJAX), return JSON. Otherwise, throw
        // so frontend error handler can render templates/500.php.
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        $isJson = false;


        if ($isJson || str_contains($accept, 'application/json')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'DB connection failed', 'details' => $conn->connect_error]);
            exit;
        }

        throw new RuntimeException('DB connection failed');
    }

    $conn->set_charset('utf8mb4');
    aj360_ensure_job_detail_columns($conn);
    aj360_ensure_admin_profile_columns($conn);
    aj360_ensure_admin_login_log_table($conn);
    aj360_ensure_admin_password_reset_table($conn);
    aj360_ensure_admin_email_otp_table($conn);
    aj360_ensure_public_user_columns($conn);
    aj360_ensure_user_email_otp_table($conn);
    return $conn;
}

function aj360_ensure_admin_password_reset_table(mysqli $conn): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    // Stores selector publicly + only hashed token in DB (like industry standard).
    // One-time use via used_at.
    $conn->query('CREATE TABLE IF NOT EXISTS admin_password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        selector VARCHAR(20) NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_admin_password_resets_selector (selector),
        INDEX idx_admin_password_resets_admin_id (admin_id),
        INDEX idx_admin_password_resets_expires_at (expires_at),
        FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}


function aj360_ensure_admin_login_log_table(mysqli $conn): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    $conn->query('CREATE TABLE IF NOT EXISTS admin_login_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        device_id VARCHAR(100) NOT NULL,
        user_agent VARCHAR(500) NULL,
        location VARCHAR(160) NULL,
        login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_admin_login_logs_admin_id (admin_id),
        FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

/** Lightweight migration for administrator profile details. */
function aj360_ensure_admin_profile_columns(mysqli $conn): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    $columns = ['display_name' => 'VARCHAR(120) NULL', 'email' => 'VARCHAR(120) NULL', 'profile_photo' => 'VARCHAR(255) NULL'];
    $existing = [];
    $result = $conn->query('SHOW COLUMNS FROM admins');
    while ($row = $result->fetch_assoc()) $existing[$row['Field']] = true;
    foreach ($columns as $name => $definition) {
        if (!isset($existing[$name])) $conn->query("ALTER TABLE admins ADD COLUMN `$name` $definition");
    }
}

/** Lightweight migration for the admin-editable job detail sections. */
function aj360_ensure_job_detail_columns(mysqli $conn): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $columns = [
        'overview' => 'TEXT NULL',
        'selection_process' => 'TEXT NULL',
        'application_fee' => 'TEXT NULL',
        'vacancy_details' => 'TEXT NULL',
        'how_to_apply' => 'TEXT NULL',
        'faqs' => 'TEXT NULL',
    ];
    $existing = [];
    $result = $conn->query('SHOW COLUMNS FROM jobs');
    while ($row = $result->fetch_assoc()) $existing[$row['Field']] = true;
    foreach ($columns as $name => $definition) {
        if (!isset($existing[$name])) $conn->query("ALTER TABLE jobs ADD COLUMN `$name` $definition");
    }
}

/** Lightweight migration for admin email OTP verification. */
function aj360_ensure_admin_email_otp_table(mysqli $conn): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    // OTP is one-time; store only token hash.
    $conn->query('CREATE TABLE IF NOT EXISTS admin_email_otps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        otp_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_admin_email_otps_admin_id (admin_id),
        INDEX idx_admin_email_otps_expires_at (expires_at),
        FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

/** Lightweight migration for public user email verification. */
function aj360_ensure_public_user_columns(mysqli $conn): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $existing = [];
    $result = $conn->query('SHOW COLUMNS FROM users');
    while ($row = $result->fetch_assoc()) $existing[$row['Field']] = true;

    if (!isset($existing['name'])) {
        $conn->query('ALTER TABLE users ADD COLUMN name VARCHAR(120) NULL AFTER id');
        $conn->query("UPDATE users SET name = SUBSTRING_INDEX(email, '@', 1) WHERE name IS NULL OR name = ''");
        $conn->query('ALTER TABLE users MODIFY name VARCHAR(120) NOT NULL');
    }

    $profileColumns = [
        'address' => 'TEXT NULL',
        'state' => 'VARCHAR(120) NULL',
        'district' => 'VARCHAR(120) NULL',
        'pin_code' => 'VARCHAR(10) NULL',
    ];
    foreach ($profileColumns as $column => $definition) {
        if (!isset($existing[$column])) {
            $conn->query("ALTER TABLE users ADD COLUMN `$column` $definition");
        }
    }

    if (!isset($existing['email_verified_at'])) {
        $conn->query('ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL');
    }
}

/** Lightweight migration for user email OTP verification. */
function aj360_ensure_user_email_otp_table(mysqli $conn): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $conn->query('CREATE TABLE IF NOT EXISTS users_email_otps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        otp_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_users_email_otps_user_id (user_id),
        INDEX idx_users_email_otps_expires_at (expires_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

