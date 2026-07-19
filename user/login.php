<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/user_security.php';


session_start();

if (!empty($_SESSION['aj360_user_id'])) {
    header('Location: ' . aj360_url('/', ['p' => 'mock-tests']));
    exit;
}

$error = '';
$expired = isset($_GET['expired']);
$csrf = aj360_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    aj360_verify_csrf((string)($_POST['csrf'] ?? ''));

    $usernameOrEmail = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $cfg = require __DIR__ . '/../config/config.php';
    $maxRequests = (int)($cfg['USER_LOGIN_RATE_LIMIT_MAX_REQUESTS'] ?? 10);
    $windowSeconds = (int)($cfg['USER_LOGIN_RATE_LIMIT_WINDOW_SECONDS'] ?? 900);

    $bucket = 'user-login|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . strtolower($usernameOrEmail);
    if (!aj360_consume_rate_limit($bucket, $maxRequests, $windowSeconds)) {
        $error = 'Too many login attempts. Please try again after some time.';
    } else {
        $mysqli = db();

        $stmt = $mysqli->prepare('SELECT id, email, password_hash, email_verified_at FROM users WHERE email=? LIMIT 1');
        $stmt->bind_param('s', $usernameOrEmail);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            $verifiedAt = (string)($user['email_verified_at'] ?? '');
            if ($verifiedAt === '') {
                $error = 'Please verify your email with OTP before logging in.';
                $_SESSION['aj360_user_pending_email_verification_id'] = (int)$user['id'];
            } else {
                $_SESSION['aj360_user_id'] = (int)$user['id'];
                $_SESSION['aj360_user_last_activity'] = time();
                header('Location: ' . aj360_url('/', ['p' => 'mock-tests']));
                exit;
            }
        } else {
            $error = 'Invalid credentials';
        }
    }
}

$csrf = aj360_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login | AssamJobs360</title>
    <link href="<?= aj360_h(aj360_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>" rel="stylesheet" />
<link href="<?= aj360_h(aj360_url('assets/aj360.css')) ?>" rel="stylesheet" />
<link href="<?= aj360_h(aj360_url('assets/auth-user.css')) ?>" rel="stylesheet" />
</head>
<body class="login-page">
<main class="container py-5 auth-shell">
    <a class="back-link" href="<?= aj360_h(aj360_url('/', ['p' => 'home'])) ?>">← Home</a>
    <section class="card shadow-sm border-0 mt-3 user-auth-card">
        <div class="card-body p-4 p-md-5">
            <span class="eyebrow">WELCOME BACK</span>
            <h1 class="user-auth-title mt-2">Sign in</h1>
            <p class="text-muted small mb-4">Login to access mock tests and keep your progress in one place.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger small"><?= aj360_h($error) ?></div>
            <?php endif; ?>

            <?php if ($expired): ?>
                <div class="alert alert-warning small">You were logged out after inactivity. Please sign in again.</div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="csrf" value="<?= aj360_h($csrf) ?>" />
                <label class="form-label small mt-2">Email</label>
                <input name="email" type="email" class="form-control" required autocomplete="email" placeholder="you@example.com" />

                <label class="form-label small mt-3">Password</label>
                <input name="password" type="password" class="form-control" required autocomplete="current-password" />

                <div class="d-grid mt-4">
                    <button class="btn btn-search" type="submit">Login</button>
                </div>
            </form>

            <div class="auth-help mt-3">
                New user? <a href="<?= aj360_h(aj360_url('user/register.php')) ?>">Create account</a>
            </div>
        </div>
    </section>
</main>
</body>
</html>

