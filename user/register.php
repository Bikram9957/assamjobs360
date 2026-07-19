<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/security.php';

session_start();

if (!empty($_SESSION['aj360_user_id'])) {
    header('Location: ' . aj360_url('/', ['p' => 'mock-tests']));
    exit;
}

$mysqli = db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    aj360_verify_csrf((string)($_POST['csrf'] ?? ''));

    $cfg = require __DIR__ . '/../config/config.php';
    $maxAttempts = (int)($cfg['USER_SIGNUP_RATE_LIMIT_MAX_REQUESTS'] ?? 10);
    $windowSeconds = (int)($cfg['USER_SIGNUP_RATE_LIMIT_WINDOW_SECONDS'] ?? 3600);

    $bucket = 'user-signup|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . strtolower((string)($_POST['email'] ?? ''));
    if (!aj360_consume_rate_limit($bucket, $maxAttempts, $windowSeconds)) {
        $error = 'Too many signup attempts. Please try again after some time.';
    } else {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } elseif ($password === '' || strlen($password) < 8) {
            $error = 'Password must contain at least 8 characters.';
        } elseif (!hash_equals($password, $confirmPassword)) {
            $error = 'Passwords do not match.';
        }

        if ($error === '' && $phone !== '' && !preg_match('/^[0-9+\-\s]{6,30}$/', $phone)) {
            $error = 'Enter a valid phone number (digits only).';
        }

        if ($error === '') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $mysqli->begin_transaction();
            try {
                $stmt = $mysqli->prepare('INSERT INTO users (email, phone, password_hash) VALUES (?, ?, ?)');
                $stmt->bind_param('sss', $email, $phone === '' ? null : $phone, $passwordHash);
                $stmt->execute();

                $userId = (int)$mysqli->insert_id;

                // OTP flow (mandatory)
                $otpTtl = (int)($cfg['USER_EMAIL_OTP_TTL_SECONDS'] ?? 900);
                $otpRateMax = (int)($cfg['USER_EMAIL_OTP_RATE_LIMIT_MAX_REQUESTS'] ?? 5);
                $otpRateWindow = (int)($cfg['USER_EMAIL_OTP_RATE_LIMIT_WINDOW_SECONDS'] ?? 3600);

                $bucketOtp = 'user-email-otp|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . strtolower($email);
                if (!aj360_consume_rate_limit($bucketOtp, $otpRateMax, $otpRateWindow)) {
                    throw new RuntimeException('OTP rate limited');
                }

                $otpRow = aj360_user_email_otp_create_record($mysqli, $userId, $otpTtl);
                $otp = $otpRow['otp'];

                $subject = 'AssamJobs360 Email Verification OTP';
                $htmlBody = 'Your verification OTP is: <b>' . aj360_h($otp) . '</b><br><br>'
                    . 'This OTP will expire in ' . max(1, (int)($otpTtl / 60)) . ' minutes.';

                aj360_send_email($email, $subject, $htmlBody);

                $_SESSION['aj360_user_pending_email_verification_id'] = $userId;

                $mysqli->commit();
                header('Location: ' . aj360_url('user/verify_email.php'));
                exit;
            } catch (Throwable $e) {
                $mysqli->rollback();
                $error = 'Unable to create your account. Please try again.';
            }
        }
    }
}

$csrf = aj360_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | AssamJobs360</title>
    <link href="<?= aj360_h(aj360_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>" rel="stylesheet">
<link href="<?= aj360_h(aj360_url('assets/aj360.css')) ?>" rel="stylesheet">
<link href="<?= aj360_h(aj360_url('assets/auth-user.css')) ?>" rel="stylesheet">
</head>
<body class="register-page">
<main class="container py-5" style="max-width:520px">
    <a class="back-link" href="<?= aj360_h(aj360_url('/', ['p' => 'home'])) ?>">← Home</a>
<section class="card shadow-sm border-0 mt-3 user-auth-card">
        <div class="card-body p-4">
            <span class="eyebrow">CREATE ACCOUNT</span>
            <h1 class="user-auth-title mt-2">Jobseeker Registration</h1>
            <p class="text-muted small">Register with email + password. OTP verification is required.</p>


            <?php if ($error): ?>
                <div class="alert alert-danger small"><?= aj360_h($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="csrf" value="<?= aj360_h($csrf) ?>">

                <label class="form-label small mt-2">Email</label>
                <input name="email" type="email" class="form-control" required autocomplete="email" placeholder="you@example.com">

                <label class="form-label small mt-3">Phone (optional)</label>
                <input name="phone" type="text" class="form-control" autocomplete="tel" placeholder="+91 9xxxxxxxxx">

                <label class="form-label small mt-3">Password</label>
                <input name="password" type="password" class="form-control" required minlength="8" autocomplete="new-password">

                <label class="form-label small mt-3">Confirm password</label>
                <input name="confirm_password" type="password" class="form-control" required minlength="8" autocomplete="new-password">

                <div class="d-grid mt-4">
                    <button class="btn btn-search" type="submit">Create Account</button>
                </div>
            </form>

            <div class="auth-help mt-3">
                Already registered? <a href="<?= aj360_h(aj360_url('user/login.php')) ?>">Login</a>
            </div>
        </div>
    </section>
</main>
</body>
</html>

