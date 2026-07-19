<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/user_security.php';


session_start();

if (empty($_SESSION['aj360_user_pending_email_verification_id'])) {
    header('Location: ' . aj360_url('user/register.php'));
    exit;
}

$userIdPending = (int)$_SESSION['aj360_user_pending_email_verification_id'];

$mysqli = db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    aj360_verify_csrf((string)($_POST['csrf'] ?? ''));
    $otp = (string)($_POST['otp'] ?? '');

    if (!aj360_user_email_otp_verify_and_consume($mysqli, $userIdPending, $otp)) {
        $error = 'Invalid or expired OTP.';
    } else {
        aj360_user_mark_email_verified($mysqli, $userIdPending);
        unset($_SESSION['aj360_user_pending_email_verification_id']);

        $_SESSION['aj360_user_id'] = $userIdPending;
        $_SESSION['aj360_user_last_activity'] = time();

        header('Location: ' . aj360_url('/', ['p' => 'mock-tests']));
        exit;
    }
}

$csrf = aj360_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Email | AssamJobs360</title>
    <link href="<?= aj360_h(aj360_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>" rel="stylesheet">
<link href="<?= aj360_h(aj360_url('assets/aj360.css')) ?>" rel="stylesheet">
<link href="<?= aj360_h(aj360_url('assets/auth-user.css')) ?>" rel="stylesheet">
</head>
<body>
<main class="container py-5" style="max-width:520px">
    <a class="back-link" href="<?= aj360_h(aj360_url('user/register.php')) ?>">← Back</a>
    <section class="card shadow-sm border-0 mt-3">
        <div class="card-body p-4">
            <span class="eyebrow">OTP VERIFICATION</span>
            <h1 class="h3 fw-bold mt-2">Verify your email</h1>
            <p class="text-muted small">Enter the 6-digit OTP sent to your email.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger small"><?= aj360_h($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="csrf" value="<?= aj360_h($csrf) ?>">

                <label class="form-label small mt-2">OTP</label>
                <input name="otp" inputmode="numeric" pattern="[0-9]{6}" class="form-control" required maxlength="6" minlength="6" placeholder="123456">

                <div class="d-grid mt-4">
                    <button class="btn btn-search" type="submit">Verify OTP</button>
                </div>
            </form>

            <div class="auth-help mt-3">
                Didn't get OTP? <a href="<?= aj360_h(aj360_url('user/register.php')) ?>">Register again</a>
            </div>
        </div>
    </section>
</main>
</body>
</html>

