<?php
declare(strict_types=1);

function aj360_h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Harden PHP session cookies as early as possible (call before any session_start). */
function aj360_harden_sessions(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => $secure,
        ]);
    }
}


function aj360_safe_url(?string $url): string {
    $url = trim((string)($url ?? ''));
    if ($url === '') return '';
    // Allow only http/https to prevent javascript/data URL injection.
    if (!preg_match('#^https?://#i', $url)) return '';
    return $url;
}


/** Return the application's installation path without assuming a folder name. */
function aj360_base_path(): string {
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $directory = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if (preg_match('#/admin$#', $directory)) {
        $directory = rtrim(dirname($directory), '/');
    }
    return $directory === '/' || $directory === '.' ? '' : $directory;
}

/** Build a same-site URL that remains valid if the project folder is renamed. */
function aj360_url(string $path = '/', array $query = []): string {
    $url = ($path === '' || $path === '/')
        ? aj360_base_path() . '/'
        : aj360_base_path() . '/' . ltrim($path, '/');
    if ($query !== []) {
        $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
    return $url;
}

function aj360_str_slug(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/i', '-', $s);
    $s = trim($s, '-');
    return $s === '' ? 'n-a' : $s;
}

function aj360_csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $cfg = require __DIR__ . '/../config/config.php';
    if (empty($_SESSION[$cfg['CSRF_SESSION_KEY']])) {
        $_SESSION[$cfg['CSRF_SESSION_KEY']] = bin2hex(random_bytes(32));
    }
    return $_SESSION[$cfg['CSRF_SESSION_KEY']];
}

function aj360_verify_csrf(?string $token): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $cfg = require __DIR__ . '/../config/config.php';
    $expected = $_SESSION[$cfg['CSRF_SESSION_KEY']] ?? '';
    if (!$token || !hash_equals($expected, $token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
}

function aj360_require_admin(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $cfg = require __DIR__ . '/../config/config.php';
    $timeout = (int)($cfg['ADMIN_SESSION_TIMEOUT_SECONDS'] ?? 3600);
    $lastActivity = (int)($_SESSION['aj360_admin_last_activity'] ?? 0);
    if ($lastActivity > 0 && (time() - $lastActivity) > $timeout) {
        $_SESSION = [];
        session_destroy();
        header('Location: ' . aj360_url('admin/login.php?expired=1'));
        exit;
    }
    if (empty($_SESSION['aj360_admin_id'])) {
        header('Location: ' . aj360_url('admin/login.php'));
        exit;
    }
    $_SESSION['aj360_admin_last_activity'] = time();
}

function aj360_log_admin_login(mysqli $conn, int $adminId): void {
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'Unknown');
    $agent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'), 0, 500);
    $deviceId = (string)($_COOKIE['aj360_device_id'] ?? '');
    if ($deviceId === '') {
        $deviceId = bin2hex(random_bytes(16));
        setcookie('aj360_device_id', $deviceId, ['expires' => time() + 31536000, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    }
    $location = in_array($ip, ['127.0.0.1', '::1'], true) ? 'Localhost' : 'Location unavailable';
    $stmt = $conn->prepare('INSERT INTO admin_login_logs (admin_id, ip_address, device_id, user_agent, location) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('issss', $adminId, $ip, $deviceId, $agent, $location);
    $stmt->execute();
}

/** File-based rate limiter; stores only a hash of the visitor IP in the system temp directory. */
function aj360_consume_rate_limit(string $bucket, int $maxAttempts, int $windowSeconds): bool {
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $key = hash('sha256', $bucket . '|' . $ip);
    $file = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'aj360-rate-' . $key . '.json';
    $now = time();
    $entries = [];
    if (is_file($file)) {
        $decoded = json_decode((string)file_get_contents($file), true);
        if (is_array($decoded)) $entries = $decoded;
    }
    $entries = array_values(array_filter($entries, static fn($timestamp): bool => is_int($timestamp) && $timestamp > ($now - $windowSeconds)));
    if (count($entries) >= $maxAttempts) return false;
    $entries[] = $now;
    file_put_contents($file, json_encode($entries), LOCK_EX);
    return true;
}

/** Enforce the configured public request limit and return an HTTP 429 when exceeded. */
function aj360_enforce_public_rate_limit(bool $jsonResponse = false): void {
    $cfg = require __DIR__ . '/../config/config.php';
    $allowed = aj360_consume_rate_limit(
        'public-request',
        (int)($cfg['RATE_LIMIT_MAX_REQUESTS'] ?? 120),
        (int)($cfg['RATE_LIMIT_WINDOW_SECONDS'] ?? 60)
    );
    if ($allowed) return;

    http_response_code(429);
    header('Retry-After: ' . (int)($cfg['RATE_LIMIT_WINDOW_SECONDS'] ?? 60));

    if ($jsonResponse) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Too many requests. Please try again shortly.']);
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');
    require __DIR__ . '/../templates/429.php';
    exit;
}




function aj360_admin_password_reset_create_record(mysqli $conn, int $adminId, int $ttlSeconds): array {
    // selector/token pattern:
    // - selector is stored in DB in clear
    // - token is never stored in clear; only SHA-256(token)
    $selector = bin2hex(random_bytes(10)); // 20 chars
    $token = bin2hex(random_bytes(32));    // 64 chars (plain shown once to admin)
    $tokenHash = hash('sha256', $token);
    $expiresAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
        ->modify(sprintf('+%d seconds', max(60, $ttlSeconds)))
        ->format('Y-m-d H:i:s');

    $stmt = $conn->prepare('INSERT INTO admin_password_resets (admin_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $adminId, $selector, $tokenHash, $expiresAt);
    $stmt->execute();

    return ['selector' => $selector, 'token' => $token, 'expires_at' => $expiresAt];
}

function aj360_admin_password_reset_verify(mysqli $conn, string $selector, string $token): ?array {
    $selector = trim($selector);
    $token = trim($token);
    if ($selector === '' || $token === '') return null;

    $tokenHash = hash('sha256', $token);
    $stmt = $conn->prepare('SELECT id, admin_id, selector, token_hash, expires_at, used_at FROM admin_password_resets WHERE selector=? LIMIT 1');
    $stmt->bind_param('s', $selector);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) return null;

    if (!hash_equals((string)$row['token_hash'], $tokenHash)) return null;
    if (!empty($row['used_at'])) return null;

    // Ensure not expired (DB stores datetime in UTC-ish format; compare as string is risky -> use timestamp)
    $expiresTs = strtotime((string)$row['expires_at']);
    if ($expiresTs === false || time() > $expiresTs) return null;

    return $row;
}

function aj360_admin_password_reset_consume(mysqli $conn, int $resetId, int $adminId, string $newPassword): bool {
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);

    $conn->begin_transaction();
    try {
        $stmt1 = $conn->prepare('UPDATE admin_password_resets SET used_at = UTC_TIMESTAMP() WHERE id=? AND admin_id=? AND used_at IS NULL');
        $stmt1->bind_param('ii', $resetId, $adminId);
        $stmt1->execute();
        if ($stmt1->affected_rows !== 1) {
            $conn->rollback();
            return false;
        }

        $stmt2 = $conn->prepare('UPDATE admins SET password_hash=? WHERE id=? LIMIT 1');
        $stmt2->bind_param('si', $hash, $adminId);
        $stmt2->execute();

        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        return false;
    }
}






