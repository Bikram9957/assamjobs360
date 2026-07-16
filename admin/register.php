<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/security.php';

session_start();
$mysqli = db();
$adminCount = (int) $mysqli->query('SELECT COUNT(*) AS total FROM admins')->fetch_assoc()['total'];
$isLoggedInAdmin = !empty($_SESSION['aj360_admin_id']);
$placeholderHash = '$2y$10$E9zjv9uK4oXWq3bCkGfC/.kK3Y6pYzVh7m7pW6m2c3Q3yYvTqB9yS';
$seed = $mysqli->query("SELECT username, password_hash FROM admins WHERE username = 'admin' LIMIT 1")->fetch_assoc();
$isPlaceholderSeed = $adminCount === 1 && $seed && hash_equals($placeholderHash, (string) $seed['password_hash']);

if ($adminCount > 0 && !$isLoggedInAdmin && !$isPlaceholderSeed) {
    http_response_code(403);
    $error = 'Admin registration is restricted. Please sign in as an administrator first.';
} else {
    $error = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    aj360_verify_csrf((string) ($_POST['csrf'] ?? ''));
    $cfg = require __DIR__ . '/../config/config.php';
    if (!aj360_consume_rate_limit('admin-signup', (int) $cfg['ADMIN_SIGNUP_RATE_LIMIT_MAX'], (int) $cfg['ADMIN_SIGNUP_RATE_LIMIT_WINDOW_SECONDS'])) {
        $error = 'Too many signup attempts. Please try again after one hour.';
    }
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($error !== '') {
        // Rate limit error was already set above.
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        $error = 'Username must be 3–50 characters and may use letters, numbers, dot, underscore or hyphen.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must contain at least 8 characters.';
    } elseif (!hash_equals($password, $confirmPassword)) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($isPlaceholderSeed) {
            $mysqli->query("DELETE FROM admins WHERE username = 'admin'");
        }
        $email = trim((string)($_POST['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } else {
            $stmt = $mysqli->prepare('INSERT INTO admins (username, password_hash, email) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $username, $hash, $email === '' ? null : $email);
        }

        if ($stmt->execute()) {
            if (!$isLoggedInAdmin)
                $_SESSION['aj360_admin_id'] = (int) $mysqli->insert_id;
            header('Location: ' . aj360_url('admin/'));
            exit;
        }
        $error = $mysqli->errno === 1062 ? 'This username is already in use.' : 'Unable to create the administrator. Please try again.';
    }
}

$csrf = aj360_csrf_token();
?><!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register Admin | AssamJobs360</title>
    <link href="<?= aj360_h(aj360_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>" rel="stylesheet">
    <link href="<?= aj360_h(aj360_url('assets/admin.css')) ?>" rel="stylesheet">
</head>

<body class="admin-page">
    <main class="container py-5" style="max-width:520px"><a class="back-link"
            href="<?= aj360_h(aj360_url('admin/login.php')) ?>">← Admin login</a>
        <section class="card shadow-sm border-0 mt-3">
            <div class="card-body p-4"><span class="eyebrow">ADMIN ACCESS</span>
                <h1 class="h3 fw-bold mt-2">Create administrator</h1>
                <p class="text-muted small">Use a unique username and a strong password.</p><?php if ($error): ?>
                    <div class="alert alert-danger small"><?= aj360_h($error) ?></div><?php else: ?>
                    <form method="post"><input type="hidden" name="csrf" value="<?= aj360_h($csrf) ?>"><label
                            class="form-label small">Username</label><input name="username" class="form-control" required
                            maxlength="50" autocomplete="username"><label
                            class="form-label small mt-3">Email (for password reset)</label><input name="email" type="email"
                            class="form-control" autocomplete="email" placeholder="you@example.com"><label
                            class="form-label small mt-3">Password</label><input name="password" type="password"
                            class="form-control" required minlength="8" autocomplete="new-password"><label
                            class="form-label small mt-3">Confirm password</label><input name="confirm_password"
                            type="password" class="form-control" required minlength="8" autocomplete="new-password">
                        <div class="d-grid mt-4"><button class="btn btn-search" type="submit">Create Admin Account</button>
                        </div>
                    </form><?php endif; ?>

            </div>
        </section>
    </main>
</body>

</html>