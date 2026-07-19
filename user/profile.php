<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/user_security.php';

aj360_require_user();

$mysqli = db();
$userId = (int)$_SESSION['aj360_user_id'];
$error = '';
$success = '';

$columns = [];
$result = $mysqli->query('SHOW COLUMNS FROM users');
while ($row = $result->fetch_assoc()) {
    $columns[$row['Field']] = true;
}

$nameSelect = isset($columns['name']) ? 'name' : "SUBSTRING_INDEX(email, '@', 1) AS name";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    aj360_verify_csrf((string)($_POST['csrf'] ?? ''));

    $stmt = $mysqli->prepare("SELECT id, {$nameSelect}, email, phone, password_hash, created_at FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $currentUser = $stmt->get_result()->fetch_assoc();

    if (!$currentUser) {
        $error = 'User account not found.';
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($name === '') {
            $error = 'Name cannot be empty.';
        } elseif ($phone !== '' && !preg_match('/^[0-9+\-\s]{6,30}$/', $phone)) {
            $error = 'Enter a valid phone number.';
        } elseif (!password_verify($currentPassword, (string)$currentUser['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif ($newPassword !== '' && strlen($newPassword) < 8) {
            $error = 'New password must contain at least 8 characters.';
        } elseif ($newPassword !== '' && !hash_equals($newPassword, $confirmPassword)) {
            $error = 'New passwords do not match.';
        }

        if ($error === '') {
            $phoneValue = $phone === '' ? null : $phone;
            $passwordHash = $newPassword !== '' ? password_hash($newPassword, PASSWORD_DEFAULT) : null;

            try {
                if ($passwordHash !== null) {
                    $stmt = $mysqli->prepare('UPDATE users SET name = ?, phone = ?, password_hash = ? WHERE id = ? LIMIT 1');
                    $stmt->bind_param('sssi', $name, $phoneValue, $passwordHash, $userId);
                } else {
                    $stmt = $mysqli->prepare('UPDATE users SET name = ?, phone = ? WHERE id = ? LIMIT 1');
                    $stmt->bind_param('ssi', $name, $phoneValue, $userId);
                }

                $stmt->execute();
                $success = 'Profile updated successfully.';
            } catch (Throwable $e) {
                $error = 'Unable to update profile. Please try again.';
                if (defined('AJ360_DEBUG') && AJ360_DEBUG) {
                    $error .= ' ' . $e->getMessage();
                }
            }
        }
    }
}

$stmt = $mysqli->prepare("SELECT id, {$nameSelect}, email, phone, created_at FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$stmt = $mysqli->prepare('
    SELECT r.score, r.total_questions, r.percentile, r.submitted_at, mt.title
    FROM results r
    INNER JOIN mock_tests mt ON mt.id = r.mock_test_id
    WHERE r.user_id = ?
    ORDER BY r.submitted_at DESC
    LIMIT 10
');
$stmt->bind_param('i', $userId);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$metaTitle = 'My Profile | AssamJobs360';
$metaDescription = 'View your AssamJobs360 account details and recent mock test scores.';

ob_start();
?>
<div class="py-4">
  <div class="row g-3 align-items-start">
    <div class="col-12 col-lg-4">
      <div class="card rounded-4 shadow-sm">
        <div class="card-body p-4">
          <span class="eyebrow">USER PROFILE</span>
          <h1 class="h4 fw-bold mt-2 mb-1"><?= aj360_h((string)($user['name'] ?? $user['email'] ?? 'User')) ?></h1>
          <div class="text-muted small mb-3">Your public account and test activity</div>
          <div class="small mb-2"><strong>Name:</strong> <?= aj360_h((string)($user['name'] ?? '')) ?></div>
          <div class="small mb-2"><strong>Email:</strong> <?= aj360_h((string)($user['email'] ?? '')) ?></div>
          <div class="small mb-2"><strong>Phone:</strong> <?= aj360_h((string)($user['phone'] ?? 'N/A')) ?></div>
          <div class="small mb-2"><strong>Joined:</strong> <?= aj360_h((string)($user['created_at'] ?? '')) ?></div>
          <div class="d-grid mt-3">
            <a class="btn btn-primary" href="<?= aj360_h(aj360_url('/', ['p' => 'mock-tests'])) ?>">Take a mock test</a>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-8">
      <div class="card rounded-4 shadow-sm mb-3">
        <div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <div>
              <span class="eyebrow">UPDATE PROFILE</span>
              <h2 class="h5 fw-bold mb-0">Edit details</h2>
            </div>
          </div>

          <?php if ($error): ?>
            <div class="alert alert-danger small"><?= aj360_h($error) ?></div>
          <?php endif; ?>

          <?php if ($success): ?>
            <div class="alert alert-success small"><?= aj360_h($success) ?></div>
          <?php endif; ?>

          <form method="post">
            <input type="hidden" name="csrf" value="<?= aj360_h(aj360_csrf_token()) ?>">

            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label small">Name</label>
                <input name="name" type="text" class="form-control" required value="<?= aj360_h((string)($user['name'] ?? '')) ?>">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small">Phone</label>
                <input name="phone" type="text" class="form-control" value="<?= aj360_h((string)($user['phone'] ?? '')) ?>" placeholder="+91 9xxxxxxxxx">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small">Current password</label>
                <input name="current_password" type="password" class="form-control" required autocomplete="current-password">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small">New password</label>
                <input name="new_password" type="password" class="form-control" minlength="8" autocomplete="new-password" placeholder="Leave blank to keep current password">
              </div>

              <div class="col-12">
                <label class="form-label small">Confirm new password</label>
                <input name="confirm_password" type="password" class="form-control" minlength="8" autocomplete="new-password">
              </div>
            </div>

            <div class="d-grid d-md-flex justify-content-md-end gap-2 mt-3">
              <button class="btn btn-search" type="submit">Save Changes</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card rounded-4 shadow-sm">
        <div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <div>
              <span class="eyebrow">MOCK TEST RESULTS</span>
              <h2 class="h5 fw-bold mb-0">Recent Scores</h2>
            </div>
            <a class="btn btn-outline-primary btn-sm" href="<?= aj360_h(aj360_url('/', ['p' => 'mock-tests'])) ?>">More tests</a>
          </div>

          <?php if (!$results): ?>
            <div class="alert alert-light border mb-0">No mock test results yet. Start a test to see your score history here.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table align-middle">
                <thead>
                  <tr>
                    <th>Test</th>
                    <th>Score</th>
                    <th>Questions</th>
                    <th>Submitted</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($results as $row): ?>
                    <tr>
                      <td><?= aj360_h((string)$row['title']) ?></td>
                      <td><strong><?= aj360_h(number_format((float)$row['score'], 2)) ?></strong></td>
                      <td><?= (int)$row['total_questions'] ?></td>
                      <td><?= aj360_h((string)$row['submitted_at']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../templates/layout.php';
