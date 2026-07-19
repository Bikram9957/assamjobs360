<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/security.php';

session_start();

header('Content-Type: text/html; charset=utf-8');

$mysqli = db();

$adminId = (int)($_SESSION['aj360_admin_pending_email_verification_id'] ?? 0);
if ($adminId <= 0) {
    header('Location: ' . aj360_url('admin/register.php'));
    exit;
}

$cfg = require __DIR__ . '/../config/config.php';
$otpTtl = (int)($cfg['ADMIN_EMAIL_OTP_TTL_SECONDS'] ?? 900);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    aj360_verify_csrf((string)($_POST['csrf'] ?? ''));

    $otp = (string)($_POST['otp'] ?? '');

    if (!aj360_admin_email_otp_verify_and_consume($mysqli, $adminId, $otp)) {
        $error = 'Invalid or expired OTP.';
    } else {
        aj360_admin_mark_email_verified($mysqli, $adminId);
        unset($_SESSION['aj360_admin_pending_email_verification_id']);
        $_SESSION['aj360_admin_id'] = $adminId;
        $_SESSION['aj360_admin_last_activity'] = time();
        $success = 'Email verified successfully. You are now logged in.';
        header('Location: ' . aj360_url('admin/'));
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
    <link href="<?= aj360_h(aj360_url('assets/admin.css')) ?>" rel="stylesheet">
</head>

<body class="admin-page">
    <main class="container py-5" style="max-width:520px">
        <a class="back-link" href="<?= aj360_h(aj360_url('admin/login.php')) ?>">← Admin login</a>
        <section class="card shadow-sm border-0 mt-3">
            <div class="card-body p-4">
                <span class="eyebrow">EMAIL VERIFICATION</span>
                <h1 class="h3 fw-bold mt-2">Enter OTP</h1>
                <p class="text-muted small">We sent a 6-digit OTP to your email. OTP expires in <?= (int)($otpTtl / 60) ?> minutes.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger small"><?= aj360_h($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success small"><?= aj360_h($success) ?></div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="csrf" value="<?= aj360_h($csrf) ?>">
                    <label class="form-label small mt-3">6-digit OTP</label>
                    <input name="otp" inputmode="numeric" pattern="\\d{6}" maxlength="6" class="form-control" required>

                    <div class="d-grid mt-4">
                        <button class="btn btn-search" type="submit">Verify OTP</button>
                    </div>
                </form>

                <div class="small text-muted mt-3">
                    If you didn't receive the email, create the admin account again.
                </div>
            </div>
        </section>
    </main>
</body>

</html>

