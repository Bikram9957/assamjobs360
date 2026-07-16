<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/db.php';

aj360_require_admin();

$mysqli = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    aj360_verify_csrf((string)($_POST['csrf'] ?? ''));

    $action = $_POST['action'] ?? 'create';
    if ($action === 'create') {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name !== '') {
            $stmt = $mysqli->prepare('INSERT IGNORE INTO categories (name) VALUES (?)');
            $stmt->bind_param('s', $name);
            $stmt->execute();
        }
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $mysqli->prepare('DELETE FROM categories WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }

    header('Location: ' . aj360_url('admin/categories.php'));
    exit;
}

$csrf = aj360_csrf_token();
$cats = $mysqli->query('SELECT id, name FROM categories ORDER BY name ASC LIMIT 100');

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Categories | AssamJobs360 Admin</title>
  <link href="<?= aj360_h(aj360_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>" rel="stylesheet" />
  <link href="<?= aj360_h(aj360_url('assets/admin.css')) ?>" rel="stylesheet" />
</head>
<body class="admin-page">
  <div class="container py-4">
    <div class="admin-topbar d-flex align-items-center justify-content-between">
      <div>
        <div><div class="admin-kicker">CONTENT MANAGEMENT</div><h1>Manage Categories</h1></div>
      </div>
      <a class="btn btn-outline-secondary btn-sm" href="<?= aj360_h(aj360_url('admin/')) ?>">Back</a>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <form method="post" class="row g-2">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
          <input type="hidden" name="action" value="create" />
          <div class="col-12 col-md-8">
            <label class="form-label small">Category Name</label>
            <input name="name" class="form-control" required />
          </div>
          <div class="col-12 col-md-4 d-grid align-self-end">
            <button class="btn btn-primary">Add</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <div class="fw-semibold mb-2">Existing Categories</div>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>Name</th><th></th></tr></thead>
            <tbody>
              <?php while ($c = $cats->fetch_assoc()): ?>
                <tr>
                  <td class="fw-semibold"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="text-end">
                    <form method="post" class="d-inline">
                      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="id" value="<?= (int)$c['id'] ?>" />
                      <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?')">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
<?php require __DIR__ . '/partials/session_timeout.php'; ?>
</body>
</html>

