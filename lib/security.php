<?php
declare(strict_types=1);

function aj360_h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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


function aj360_require_login(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['aj360_user_id'])) {
        header('Location: ' . aj360_url('login.php'));
        exit;
    }
}

