<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/security.php';

session_start();

header('Content-Type: text/html; charset=utf-8');

if (!empty($_SESSION['aj360_admin_id'])) {
    header('Location: ' . aj360_url('admin/'));
    exit;
}

$mysqli = db();
$error = '';
$success = '';
$generatedLink = '';

$cfg = require __DIR__ . '/../config/config.php';
$ttl = (int)($cfg['ADMIN_PASSWORD_RESET_TTL_SECONDS'] ?? 1800); // 30 mins
$rateMax = (int)($cfg['ADMIN_FORGOT_PASSWORD_RATE_LIMIT_MAX_REQUESTS'] ?? 5);
$rateWindow = (int)($cfg['ADMIN_FORGOT_PASSWORD_RATE_LIMIT_WINDOW_SECONDS'] ?? 3600);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    aj360_verify_csrf((string)($_POST['csrf'] ?? ''));

    $username = trim((string)($_POST['username'] ?? ''));

    // Avoid overly strict validation to prevent enumeration through error differences.
    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        $error = 'Enter a valid username.';
    } else {
        $bucket = 'admin-forgot-password|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . strtolower($username);
        if (!aj360_consume_rate_limit($bucket, $rateMax, $rateWindow)) {
            $error = 'Too many requests. Please try again after some time.';
        } else {
            // Always return generic message; only show the reset token/link if admin exists.
            $stmt = $mysqli->prepare('SELECT id FROM admins WHERE username=? LIMIT 1');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();

            $success = 'If an account with that username exists, you can reset your password using the provided code/link.';

            if ($admin) {
                $reset = aj360_admin_password_reset_create_record($mysqli, (int)$admin['id'], $ttl);
                $selector = $reset['selector'];
                $token = $reset['token'];
                $generatedLink = aj360_url('admin/reset_password.php', [
                    'selector' => $selector,
                    'token' => $token,
                ]);

                // Display token/link only once immediately after generation.
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
  <title>Reset Admin Password | AssamJobs360</title>
  <link href="<?= aj360_h(aj360_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>" rel="stylesheet" />
  <link href="<?= aj360_h(aj360_url('assets/admin.css')) ?>" rel="stylesheet" />
</head>
<body class="admin-page">
  <main class="container py-5" style="max-width:560px">
    <a class="back-link" href="<?= aj360_h(aj360_url('admin/login.php')) ?>">← Admin login</a>

    <section class="card shadow-sm border-0 mt-3">
      <div class="card-body p-4">
        <span class="eyebrow">ACCOUNT RECOVERY</span>
        <h1 class="h3 fw-bold mt-2">Forgot password</h1>
        <p class="text-muted small">Enter your admin username. You'll get a one-time reset link/code.</p>

        <?php if ($error): ?>
          <div class="alert alert-danger small"><?= aj360_h($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="alert alert-info small"><?= aj360_h($success) ?></div>
        <?php endif; ?>

        <?php if ($generatedLink !== ''): ?>
          <div class="alert alert-success small">
            <div class="fw-bold mb-2">Your reset link (one-time):</div>
            <div style="word-break:break-all"> 
              <a href="<?= aj360_h($generatedLink) ?>" target="_blank" rel="noopener noreferrer">Reset password</a>
            </div>
            <div class="text-muted mt-2" style="font-size:12px">Use it within <?= (int)($ttl/60) ?> minutes.</div>
          </div>
        <?php endif; ?>

        <form method="post" class="mt-3">
          <input type="hidden" name="csrf" value="<?= aj360_h($csrf) ?>" />

          <label class="form-label small">Admin Username</label>
          <input name="username" class="form-control" required maxlength="50" autocomplete="username" value="<?= isset($_POST['username']) ? aj360_h((string)$_POST['username']) : '' ?>" />

          <div class="d-grid mt-4">
            <button class="btn btn-search" type="submit">Generate reset link</button>
          </div>
        </form>
      </div>
    </section>
  </main>
</body>
</html>

