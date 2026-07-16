<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/db.php';

aj360_require_admin();

$mysqli = db();
$jobCount = (int)$mysqli->query("SELECT COUNT(*) AS c FROM jobs")->fetch_assoc()['c'];
$categoryCount = (int)$mysqli->query("SELECT COUNT(*) AS c FROM categories")->fetch_assoc()['c'];
$mockTestCount = (int)$mysqli->query("SELECT COUNT(*) AS c FROM mock_tests")->fetch_assoc()['c'];
$adminId = (int)$_SESSION['aj360_admin_id'];
$adminStmt = $mysqli->prepare('SELECT username, display_name, profile_photo FROM admins WHERE id = ? LIMIT 1');
$adminStmt->bind_param('i', $adminId);
$adminStmt->execute();
$admin = $adminStmt->get_result()->fetch_assoc() ?: ['username' => 'A', 'display_name' => ''];
$adminName = (string)($admin['display_name'] ?: $admin['username']);
$adminInitial = strtoupper(substr($adminName, 0, 1));

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard | AssamJobs360</title>
  <link href="<?= aj360_h(aj360_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>" rel="stylesheet" />
  <link href="<?= aj360_h(aj360_url('assets/admin.css')) ?>" rel="stylesheet" />
</head>
<body class="admin-page">
  <div class="container py-4">
    <div class="admin-topbar d-flex align-items-center justify-content-between">
      <div>
        <div class="admin-kicker">CONTROL CENTRE</div><h1>AssamJobs360 Admin</h1>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a class="text-decoration-none text-white d-flex align-items-center gap-2" href="<?= aj360_h(aj360_url('admin/profile.php')) ?>" title="My Profile">
          <?php if (!empty($admin['profile_photo'])): ?>
            <img src="<?= aj360_h(aj360_url($admin['profile_photo'])) ?>" alt="Profile" style="width:34px;height:34px;border-radius:50%;object-fit:cover">
          <?php else: ?>
            <span style="width:34px;height:34px;border-radius:50%;display:grid;place-items:center;background:#f47b20;color:#fff;font-weight:700"><?= aj360_h($adminInitial) ?></span>
          <?php endif; ?>
          <span class="d-none d-sm-inline small fw-semibold"><?= aj360_h($adminName) ?></span>
        </a>
        <a class="btn btn-outline-secondary btn-sm" href="<?= aj360_h(aj360_url('admin/logout.php')) ?>">Logout</a>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><div class="small text-muted text-uppercase fw-semibold">Total Jobs</div><div class="display-6 fw-bold text-primary"><?= $jobCount ?></div><a class="small text-decoration-none" href="<?= aj360_h(aj360_url('admin/jobs.php')) ?>">Manage jobs →</a></div></div></div>
      <div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><div class="small text-muted text-uppercase fw-semibold">Categories</div><div class="display-6 fw-bold" style="color:#f47b20"><?= $categoryCount ?></div><a class="small text-decoration-none" href="<?= aj360_h(aj360_url('admin/categories.php')) ?>">Manage categories →</a></div></div></div>
      <div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><div class="small text-muted text-uppercase fw-semibold">Mock Tests</div><div class="display-6 fw-bold text-success"><?= $mockTestCount ?></div><a class="small text-decoration-none" href="<?= aj360_h(aj360_url('admin/mock_tests.php')) ?>">Manage mock tests →</a></div></div></div>
    </div>
    <div class="row g-3">
      <div class="col-12">
        <div class="card manage-card"><div class="card-body">
          <div class="d-flex justify-content-between align-items-end mb-3"><div><span class="admin-kicker" style="color:#1167d8">QUICK ACTIONS</span><h2 class="h5 fw-bold mb-0 mt-1">Manage your portal</h2></div><span class="small text-muted">Choose a workspace</span></div>
          <div class="manage-grid">
            <a class="manage-tile primary" href="<?= aj360_h(aj360_url('admin/jobs.php')) ?>"><span class="manage-icon">J</span><span><b>Jobs</b><small>Create, edit and publish vacancies</small></span><i>→</i></a>
            <a class="manage-tile" href="<?= aj360_h(aj360_url('admin/categories.php')) ?>"><span class="manage-icon">C</span><span><b>Categories</b><small>Organize job classifications</small></span><i>→</i></a>
            <a class="manage-tile" href="<?= aj360_h(aj360_url('admin/mock_tests.php')) ?>"><span class="manage-icon">M</span><span><b>Mock Tests</b><small>Build tests and questions</small></span><i>→</i></a>
            <a class="manage-tile" href="<?= aj360_h(aj360_url('admin/profile.php')) ?>"><span class="manage-icon">P</span><span><b>My Profile</b><small>Update account settings</small></span><i>→</i></a>
            <a class="manage-tile" href="<?= aj360_h(aj360_url('admin/login_activity.php')) ?>"><span class="manage-icon">L</span><span><b>Login Activity</b><small>Review account security</small></span><i>→</i></a>
            <a class="manage-tile" href="<?= aj360_h(aj360_url('admin/register.php')) ?>"><span class="manage-icon">A</span><span><b>Add Admin</b><small>Create another admin account</small></span><i>→</i></a>
          </div>
        </div></div>
      </div>
    </div>
  </div>
<?php require __DIR__ . '/partials/session_timeout.php'; ?>
</body>
</html>

