<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/security.php';

session_start();

if (!empty($_SESSION['aj360_admin_id'])) {
    header('Location: ' . aj360_url('admin/'));
    exit;
}

header('Content-Type: text/html; charset=utf-8');

$mysqli = db();
$error = '';
$success = '';

$selector = (string)($_GET['selector'] ?? '');
$token = (string)($_GET['token'] ?? '');

// Rate limit resets by IP
$cfg = require __DIR__ . '/../config/config.php';
$rateMax = (int)($cfg['ADMIN_RESET_PASSWORD_RATE_LIMIT_MAX_REQUESTS'] ?? 5);
$rateWindow = (int)($cfg['ADMIN_RESET_PASSWORD_RATE_LIMIT_WINDOW_SECONDS'] ?? 3600);

$resetRow = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($selector !== '' && $token !== '') {
        $resetRow = aj360_admin_password_reset_verify($mysqli, $selector, $token);
    }
    if ($resetRow === null) {
        // Generic error; keep form visible to avoid token oracle behavior.
        $error = 'Invalid or expired reset link.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    aj360_verify_csrf((string)($_POST['csrf'] ?? ''));

    $selector = (string)($_POST['selector'] ?? '');
    $token = (string)($_POST['token'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($newPassword === '' || strlen($newPassword) < 8) {
        $error = 'Password must contain at least 8 characters.';
    } elseif (!hash_equals($newPassword, $confirmPassword)) {
        $error = 'Passwords do not match.';
    } else {
        $bucket = 'admin-reset-password|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (!aj360_consume_rate_limit($bucket, $rateMax, $rateWindow)) {
            $error = 'Too many attempts. Please try again later.';
        } else {
            $resetRow = aj360_admin_password_reset_verify($mysqli, $selector, $token);
            if ($resetRow === null) {
                $error = 'Invalid or expired reset link.';
            } else {
                $resetId = (int)$resetRow['id'];
                $adminId = (int)$resetRow['admin_id'];
                if (aj360_admin_password_reset_consume($mysqli, $resetId, $adminId, $newPassword)) {
                    $success = 'Password updated successfully. You can login now.';
                    $selector = '';
                    $token = '';
                } else {
                    $error = 'Unable to reset password. Please try again.';
                }
            }
        }
    }
}

$csrf = aj360_csrf_token();
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Set New Password | AssamJobs360</title>
  <link href="<?= aj360_h(aj360_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>" rel="stylesheet" />
  <link href="<?= aj360_h(aj360_url('assets/admin.css')) ?>" rel="stylesheet" />
</head>
<body class="admin-page">
  <main class="container py-5" style="max-width:560px">
    <a class="back-link" href="<?= aj360_h(aj360_url('admin/login.php')) ?>">← Admin login</a>

    <section class="card shadow-sm border-0 mt-3">
      <div class="card-body p-4">
        <span class="eyebrow">RESET PASSWORD</span>
        <h1 class="h3 fw-bold mt-2">Create a new password</h1>
        <p class="text-muted small">Use the reset link/code generated from Forgot Password.</p>

        <?php if ($success): ?>
          <div class="alert alert-success small"><?= aj360_h($success) ?></div>
          <div class="d-grid mt-3">
            <a class="btn btn-search" href="<?= aj360_h(aj360_url('admin/login.php')) ?>">Go to login</a>
          </div>
        <?php else: ?>
          <?php if ($error): ?>
            <div class="alert alert-danger small"><?= aj360_h($error) ?></div>
          <?php endif; ?>

          <form method="post" class="mt-3">
            <input type="hidden" name="csrf" value="<?= aj360_h($csrf) ?>" />
            <input type="hidden" name="selector" value="<?= aj360_h($selector) ?>" />
            <input type="hidden" name="token" value="<?= aj360_h($token) ?>" />

            <label class="form-label small">New Password</label>
            <input name="new_password" type="password" class="form-control" minlength="8" autocomplete="new-password" required />

            <label class="form-label small mt-3">Confirm Password</label>
            <input name="confirm_password" type="password" class="form-control" minlength="8" autocomplete="new-password" required />

            <div class="d-grid mt-4">
              <button class="btn btn-search" type="submit">Update Password</button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </section>
  </main>
</body>
</html>

