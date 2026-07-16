<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/security.php';

session_start();

// A logged-in administrator should never see the login form again.
if (!empty($_SESSION['aj360_admin_id'])) {
    header('Location: ' . aj360_url('admin/'));
    exit;
}

header('Content-Type: text/html; charset=utf-8');

$error = '';
$expired = isset($_GET['expired']);
$adminCount = (int)db()->query('SELECT COUNT(*) AS total FROM admins')->fetch_assoc()['total'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $mysqli = db();
    $stmt = $mysqli->prepare('SELECT id, username, password_hash FROM admins WHERE username=? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $admin = $res->fetch_assoc();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['aj360_admin_id'] = (int)$admin['id'];
        $_SESSION['aj360_admin_last_activity'] = time();
        aj360_log_admin_login($mysqli, (int)$admin['id']);
        header('Location: ' . aj360_url('admin/'));
        exit;
    }

    $error = 'Invalid credentials';
}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Login | AssamJobs360</title>
  <link href="<?= aj360_h(aj360_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>" rel="stylesheet" />
  <link href="<?= aj360_h(aj360_url('assets/admin.css')) ?>" rel="stylesheet" />
</head>
<body class="admin-page auth-page">
  <main class="auth-shell">
    <section class="auth-brand-panel">
      <a class="auth-brand" href="<?= aj360_h(aj360_url()) ?>"><span>AJ</span> AssamJobs360</a>
      <div class="auth-brand-copy"><span class="admin-kicker">ADMIN CONTROL CENTRE</span><h1>Manage Assam's job opportunities with confidence.</h1><p>Securely publish jobs, manage mock tests and keep your portal up to date.</p></div>
      <div class="auth-feature-list"><div><b>✓</b><span><strong>Secure access</strong><small>Protected admin workspace</small></span></div><div><b>✓</b><span><strong>Quick publishing</strong><small>Manage jobs in minutes</small></span></div></div>
    </section>
    <section class="auth-form-panel">
      <div class="auth-form-wrap"><div class="auth-mobile-brand"><span>AJ</span> AssamJobs360</div><span class="eyebrow">WELCOME BACK</span><h2>Sign in to your account</h2><p class="text-muted small mb-4">Enter your credentials to access the admin dashboard.</p>
      <?php if ($error): ?><div class="alert alert-danger small"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
      <?php if ($expired): ?><div class="alert alert-warning small">You were logged out after 60 minutes of inactivity.</div><?php endif; ?>
      <form method="post" class="auth-form">
        <label class="form-label">Username</label><input name="username" class="form-control" placeholder="Enter your username" autocomplete="username" required>
        <label class="form-label mt-3">Password</label><input name="password" type="password" class="form-control" placeholder="Enter your password" autocomplete="current-password" required>
        <div class="d-grid mt-4"><button class="btn auth-login-btn">Sign In to Dashboard <span>→</span></button></div>
      </form>
      <div class="auth-help"><span>Administrator access only</span><a href="<?= aj360_h(aj360_url('admin/register.php')) ?>">Create admin account</a></div>
      </div>
    </section>
  </main>
</body>
</html>

