<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';

function aj360_require_user(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $cfg = require __DIR__ . '/../config/config.php';
    $timeout = (int)($cfg['USER_SESSION_TIMEOUT_SECONDS'] ?? 3600);
    $lastActivity = (int)($_SESSION['aj360_user_last_activity'] ?? 0);

    if ($lastActivity > 0 && (time() - $lastActivity) > $timeout) {
        $_SESSION = [];
        session_destroy();
        header('Location: ' . aj360_url('user/login.php?expired=1'));
        exit;
    }

    if (empty($_SESSION['aj360_user_id'])) {
        header('Location: ' . aj360_url('user/login.php'));
        exit;
    }

    $_SESSION['aj360_user_last_activity'] = time();
}

function aj360_user_email_otp_create_record(mysqli $conn, int $userId, int $ttlSeconds): array {
    $otp = (string)random_int(100000, 999999);
    $otpHash = hash('sha256', $otp);

    $expiresAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
        ->modify(sprintf('+%d seconds', max(60, $ttlSeconds)))
        ->format('Y-m-d H:i:s');

    $stmt = $conn->prepare('INSERT INTO users_email_otps (user_id, otp_hash, expires_at) VALUES (?, ?, ?)');
    $stmt->bind_param('iss', $userId, $otpHash, $expiresAt);
    $stmt->execute();

    return ['otp' => $otp, 'expires_at' => $expiresAt];
}

function aj360_user_email_otp_verify_and_consume(mysqli $conn, int $userId, string $otp): bool {
    $otp = trim($otp);
    if ($otp === '' || !preg_match('/^\d{6}$/', $otp)) return false;

    $otpHash = hash('sha256', $otp);

    $stmt = $conn->prepare('SELECT id, expires_at, used_at FROM users_email_otps WHERE user_id=? AND otp_hash=? ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('is', $userId, $otpHash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) return false;
    if (!empty($row['used_at'])) return false;

    $expiresTs = strtotime((string)$row['expires_at']);
    if ($expiresTs === false || time() > $expiresTs) return false;

    $consume = $conn->prepare('UPDATE users_email_otps SET used_at = UTC_TIMESTAMP() WHERE id=? AND used_at IS NULL');
    $consume->bind_param('i', (int)$row['id']);
    $consume->execute();

    return $consume->affected_rows === 1;
}

function aj360_user_mark_email_verified(mysqli $conn, int $userId): void {
    $conn->query('ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL');
    $stmt = $conn->prepare('UPDATE users SET email_verified_at = UTC_TIMESTAMP() WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
}

