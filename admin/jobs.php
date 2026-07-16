<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/db.php';

aj360_require_admin();

$mysqli = db();
$editId = (int)($_GET['edit_id'] ?? 0);
$editingJob = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    aj360_verify_csrf((string)($_POST['csrf'] ?? ''));

    $action = $_POST['action'] ?? 'create';

    $job_name = trim((string)($_POST['job_name'] ?? ''));
    $department = trim((string)($_POST['department'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));
    $district = trim((string)($_POST['district'] ?? ''));
    $qualification = trim((string)($_POST['qualification'] ?? ''));

    $age_limit = trim((string)($_POST['age_limit'] ?? ''));
    $salary = trim((string)($_POST['salary'] ?? ''));
    $last_date = trim((string)($_POST['last_date'] ?? ''));

    $apply_url = trim((string)($_POST['apply_url'] ?? ''));
    $notification_pdf_url = trim((string)($_POST['notification_pdf_url'] ?? ''));
    $official_website_url = trim((string)($_POST['official_website_url'] ?? ''));
    $overview = trim((string)($_POST['overview'] ?? ''));
    $selection_process = trim((string)($_POST['selection_process'] ?? ''));
    $application_fee = trim((string)($_POST['application_fee'] ?? ''));
    $vacancy_details = trim((string)($_POST['vacancy_details'] ?? ''));
    $how_to_apply = trim((string)($_POST['how_to_apply'] ?? ''));
    $faqs = trim((string)($_POST['faqs'] ?? ''));

    $job_slug = aj360_str_slug($job_name . '-' . $department . '-' . $last_date);

    if ($action === 'create') {
        $stmt = $mysqli->prepare("INSERT INTO jobs (job_name, department, category, district, qualification, age_limit, salary, last_date, apply_url, notification_pdf_url, official_website_url, overview, selection_process, application_fee, vacancy_details, how_to_apply, faqs, job_slug)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            'ssssssssssssssssss',
            $job_name,
            $department,
            $category,
            $district,
            $qualification,
            $age_limit,
            $salary,
            $last_date,
            $apply_url,
            $notification_pdf_url,
            $official_website_url,
            $overview,
            $selection_process,
            $application_fee,
            $vacancy_details,
            $how_to_apply,
            $faqs,
            $job_slug
        );
        $stmt->execute();
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $mysqli->prepare('DELETE FROM jobs WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $mysqli->prepare('UPDATE jobs SET job_name=?, department=?, category=?, district=?, qualification=?, age_limit=?, salary=?, last_date=?, apply_url=?, notification_pdf_url=?, official_website_url=?, overview=?, selection_process=?, application_fee=?, vacancy_details=?, how_to_apply=?, faqs=?, job_slug=? WHERE id=?');
        $stmt->bind_param('ssssssssssssssssssi', $job_name, $department, $category, $district, $qualification, $age_limit, $salary, $last_date, $apply_url, $notification_pdf_url, $official_website_url, $overview, $selection_process, $application_fee, $vacancy_details, $how_to_apply, $faqs, $job_slug, $id);
        $stmt->execute();
    }

    header('Location: ' . aj360_url('admin/jobs.php'));
    exit;
}

$csrf = aj360_csrf_token();

if ($editId > 0) {
    $stmt = $mysqli->prepare('SELECT * FROM jobs WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editingJob = $stmt->get_result()->fetch_assoc() ?: null;
}

$jobs = $mysqli->query('SELECT id, job_name, department, category, district, qualification, last_date, job_slug FROM jobs ORDER BY last_date ASC LIMIT 100');

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Jobs | AssamJobs360 Admin</title>
  <link href="<?= aj360_h(aj360_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>" rel="stylesheet" />
  <link href="<?= aj360_h(aj360_url('assets/admin.css')) ?>" rel="stylesheet" />
</head>
<body class="admin-page">
  <div class="container py-4">
    <div class="admin-topbar d-flex align-items-center justify-content-between">
      <div>
        <div><div class="admin-kicker">JOB MANAGEMENT</div><h1>Manage Jobs</h1></div>
      </div>
      <a class="btn btn-outline-secondary btn-sm" href="<?= aj360_h(aj360_url('admin/')) ?>">Back</a>
    </div>

    <div class="card shadow-sm mb-4" id="job-form">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h6 fw-bold mb-0"><?= $editingJob ? 'Edit Job' : 'Create Job' ?></h2><?php if ($editingJob): ?><a class="small" href="<?= aj360_h(aj360_url('admin/jobs.php')) ?>">Cancel edit</a><?php endif; ?></div>
        <form method="post" class="row g-2">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
          <input type="hidden" name="action" value="<?= $editingJob ? 'update' : 'create' ?>" />
          <?php if ($editingJob): ?><input type="hidden" name="id" value="<?= (int)$editingJob['id'] ?>" /><?php endif; ?>

          <div class="col-12 col-md-6">
            <label class="form-label small">Job Name</label>
            <input name="job_name" class="form-control" value="<?= aj360_h($editingJob['job_name'] ?? '') ?>" required />
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label small">Department</label>
            <input name="department" class="form-control" value="<?= aj360_h($editingJob['department'] ?? '') ?>" required />
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label small">Category</label>
            <input name="category" class="form-control" value="<?= aj360_h($editingJob['category'] ?? '') ?>" required />
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label small">District</label>
            <input name="district" class="form-control" value="<?= aj360_h($editingJob['district'] ?? '') ?>" required />
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label small">Qualification</label>
            <input name="qualification" class="form-control" value="<?= aj360_h($editingJob['qualification'] ?? '') ?>" required />
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label small">Last Date</label>
            <input name="last_date" type="date" class="form-control" value="<?= aj360_h($editingJob['last_date'] ?? '') ?>" />
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label small">Age Limit</label>
            <input name="age_limit" class="form-control" value="<?= aj360_h($editingJob['age_limit'] ?? '') ?>" />
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label small">Salary</label>
            <input name="salary" class="form-control" value="<?= aj360_h($editingJob['salary'] ?? '') ?>" />
          </div>

          <div class="col-12">
            <label class="form-label small">Apply URL (official)</label>
            <input name="apply_url" class="form-control" value="<?= aj360_h($editingJob['apply_url'] ?? '') ?>" />
          </div>
          <div class="col-12">
            <label class="form-label small">Notification PDF URL</label>
            <input name="notification_pdf_url" class="form-control" value="<?= aj360_h($editingJob['notification_pdf_url'] ?? '') ?>" />
          </div>
          <div class="col-12">
            <label class="form-label small">Official Website URL</label>
            <input name="official_website_url" class="form-control" value="<?= aj360_h($editingJob['official_website_url'] ?? '') ?>" />
          </div>

          <div class="col-12"><hr class="my-2"><div class="small fw-bold text-primary">Job Details Page Content</div></div>
          <div class="col-12">
            <label class="form-label small">Overview</label>
            <textarea name="overview" class="form-control" rows="3" placeholder="Short job overview"><?= aj360_h($editingJob['overview'] ?? '') ?></textarea>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label small">Selection Process</label>
            <textarea name="selection_process" class="form-control" rows="3" placeholder="Written exam, interview, document verification... "><?= aj360_h($editingJob['selection_process'] ?? '') ?></textarea>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label small">Application Fee</label>
            <textarea name="application_fee" class="form-control" rows="3" placeholder="Category-wise fee details"><?= aj360_h($editingJob['application_fee'] ?? '') ?></textarea>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label small">Vacancy Details</label>
            <textarea name="vacancy_details" class="form-control" rows="3" placeholder="Post-wise vacancies"><?= aj360_h($editingJob['vacancy_details'] ?? '') ?></textarea>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label small">How to Apply</label>
            <textarea name="how_to_apply" class="form-control" rows="3" placeholder="Step-by-step application instructions"><?= aj360_h($editingJob['how_to_apply'] ?? '') ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label small">FAQs</label>
            <textarea name="faqs" class="form-control" rows="3" placeholder="One FAQ per line. Example: Who can apply? | Graduates can apply."><?= aj360_h($editingJob['faqs'] ?? '') ?></textarea>
          </div>

          <div class="col-12 d-grid">
            <button class="btn btn-primary"><?= $editingJob ? 'Save Changes' : 'Create Job' ?></button>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <div class="fw-semibold mb-2">Existing Jobs</div>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Job</th>
                <th>Dept</th>
                <th>Category</th>
                <th>Last Date</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php while ($j = $jobs->fetch_assoc()): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($j['job_name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="small text-muted">Slug: <?= htmlspecialchars($j['job_slug'], ENT_QUOTES, 'UTF-8') ?></div>
                  </td>
                  <td><?= htmlspecialchars($j['department'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars($j['category'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars($j['last_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="<?= aj360_h(aj360_url('admin/jobs.php', ['edit_id'=>(int)$j['id']])) ?>#job-form">Edit</a>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="id" value="<?= (int)$j['id'] ?>" />
                      <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this job?')">Delete</button>
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

