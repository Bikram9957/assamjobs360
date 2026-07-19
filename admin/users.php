<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/db.php';
aj360_require_admin();

$mysqli = db();
$adminId = (int)$_SESSION['aj360_admin_id'];
$csrf = aj360_csrf_token();
$message = '';
$error = '';
$editUser = null;
$search = trim((string)($_GET['q'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    aj360_verify_csrf((string)($_POST['csrf'] ?? ''));
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === $adminId) {
            $error = 'You cannot delete your own admin account from this screen.';
        } else {
            $stmt = $mysqli->prepare('DELETE FROM users WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $message = 'User deleted successfully.';
            } else {
                $error = 'Unable to delete user.';
            }
        }
    }

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $state = trim((string)($_POST['state'] ?? ''));
        $district = trim((string)($_POST['district'] ?? ''));
        $pinCode = trim((string)($_POST['pin_code'] ?? ''));
        $newPassword = (string)($_POST['new_password'] ?? '');

        if ($name === '') {
            $error = 'Name is required.';
        } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } elseif ($phone !== '' && !preg_match('/^[0-9+\-\s]{6,30}$/', $phone)) {
            $error = 'Enter a valid phone number.';
        } elseif ($state === '') {
            $error = 'State is required.';
        } elseif ($district === '') {
            $error = 'District is required.';
        } elseif ($pinCode === '' || !preg_match('/^\d{6}$/', $pinCode)) {
            $error = 'Enter a valid 6-digit pin code.';
        } elseif ($newPassword !== '' && strlen($newPassword) < 8) {
            $error = 'New password must be at least 8 characters.';
        }

        if ($error === '') {
            $passwordHash = $newPassword !== '' ? password_hash($newPassword, PASSWORD_DEFAULT) : null;
            try {
                if ($passwordHash !== null) {
                    $stmt = $mysqli->prepare('UPDATE users SET name=?, email=?, phone=?, address=?, state=?, district=?, pin_code=?, password_hash=? WHERE id=? LIMIT 1');
                    $stmt->bind_param('ssssssssi', $name, $email, $phone, $address, $state, $district, $pinCode, $passwordHash, $id);
                } else {
                    $stmt = $mysqli->prepare('UPDATE users SET name=?, email=?, phone=?, address=?, state=?, district=?, pin_code=? WHERE id=? LIMIT 1');
                    $stmt->bind_param('sssssssi', $name, $email, $phone, $address, $state, $district, $pinCode, $id);
                }
                $stmt->execute();
                if ($stmt->affected_rows >= 0) {
                    $message = 'User updated successfully.';
                }
            } catch (Throwable $e) {
                if (str_contains(strtolower($e->getMessage()), 'duplicate')) {
                    $error = 'That email is already in use.';
                } else {
                    $error = 'Unable to update user.';
                    if (defined('AJ360_DEBUG') && AJ360_DEBUG) {
                        $error .= ' ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $stmt = $mysqli->prepare('SELECT id, name, email, phone, address, state, district, pin_code, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editUser = $stmt->get_result()->fetch_assoc() ?: null;
}

$sql = 'SELECT id, name, email, phone, address, state, district, pin_code, created_at FROM users';
$params = [];
$types = '';
if ($search !== '') {
    $sql .= ' WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
    $types = 'sss';
}
$sql .= ' ORDER BY created_at DESC LIMIT 200';
$stmt = $mysqli->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Users | AssamJobs360 Admin</title>
  <link href="<?= aj360_h(aj360_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>" rel="stylesheet" />
  <link href="<?= aj360_h(aj360_url('assets/admin.css')) ?>" rel="stylesheet" />
</head>
<body class="admin-page">
  <div class="container py-4">
    <div class="admin-topbar d-flex align-items-center justify-content-between">
      <div>
        <div class="admin-kicker">USER MANAGEMENT</div>
        <h1>Manage Users</h1>
      </div>
      <a class="btn btn-outline-secondary btn-sm" href="<?= aj360_h(aj360_url('admin/')) ?>">Back</a>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-success small"><?= aj360_h($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger small"><?= aj360_h($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <form class="row g-2" method="get">
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" name="q" value="<?= aj360_h($search) ?>" placeholder="Search by name, email, or phone">
          </div>
          <div class="col-12 col-md-4 d-grid">
            <button class="btn btn-search">Search Users</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h6 fw-bold mb-0"><?= $editUser ? 'Edit User' : 'Quick Edit' ?></h2>
          <?php if ($editUser): ?><a class="small" href="<?= aj360_h(aj360_url('admin/users.php')) ?>">Cancel edit</a><?php endif; ?>
        </div>
        <?php if ($editUser): ?>
          <form method="post" class="row g-3">
            <input type="hidden" name="csrf" value="<?= aj360_h($csrf) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int)$editUser['id'] ?>">
            <div class="col-12 col-md-4">
              <label class="form-label small">Name</label>
              <input class="form-control" name="name" value="<?= aj360_h($editUser['name'] ?? '') ?>" required>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label small">Email</label>
              <input class="form-control" name="email" type="email" value="<?= aj360_h($editUser['email'] ?? '') ?>" required>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label small">Phone</label>
              <input class="form-control" name="phone" value="<?= aj360_h($editUser['phone'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label small">Address</label>
              <textarea class="form-control" name="address" rows="2"><?= aj360_h($editUser['address'] ?? '') ?></textarea>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label small">State</label>
              <input class="form-control" name="state" value="<?= aj360_h($editUser['state'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label small">District</label>
              <input class="form-control" name="district" value="<?= aj360_h($editUser['district'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label small">Pin Code</label>
              <input class="form-control" name="pin_code" value="<?= aj360_h($editUser['pin_code'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label small">New Password</label>
              <input class="form-control" name="new_password" type="password" minlength="8" placeholder="Leave blank to keep current password">
            </div>
            <div class="col-12 d-grid d-md-flex justify-content-md-end">
              <button class="btn btn-primary">Save User</button>
            </div>
          </form>
        <?php else: ?>
          <div class="text-muted small">Use the table below to edit or delete any public user.</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <div class="fw-semibold mb-3">All Users</div>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Location</th>
                <th>Joined</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$users): ?>
                <tr><td colspan="6" class="text-muted">No users found.</td></tr>
              <?php else: foreach ($users as $u): ?>
                <tr>
                  <td class="fw-semibold"><?= aj360_h((string)$u['name']) ?></td>
                  <td><?= aj360_h((string)$u['email']) ?></td>
                  <td><?= aj360_h((string)($u['phone'] ?? '')) ?></td>
                  <td><?= aj360_h(trim((string)($u['state'] ?? '') . ' ' . (string)($u['district'] ?? '') . ' ' . (string)($u['pin_code'] ?? ''))) ?></td>
                  <td><?= aj360_h((string)$u['created_at']) ?></td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="<?= aj360_h(aj360_url('admin/users.php', ['edit_id' => (int)$u['id']])) ?>">Edit</a>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="csrf" value="<?= aj360_h($csrf) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user and all their results?')">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
<?php require __DIR__ . '/partials/session_timeout.php'; ?>
</body>
</html>
