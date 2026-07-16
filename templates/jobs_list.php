<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/security.php';

$metaTitle = 'Latest Assam Government Jobs | AssamJobs360';
$metaDescription = 'Browse latest Assam government jobs with eligibility, deadlines and official application links.';

$department = trim((string)($_GET['department'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$district = trim((string)($_GET['district'] ?? ''));
$qualification = trim((string)($_GET['qualification'] ?? ''));

ob_start();
?>
<div class="py-3">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h1 class="h4 fw-bold mb-1">Latest Jobs</h1>
      <div class="text-muted small">Quick eligibility + verified links</div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-md-4">
      <div class="card rounded-4 shadow-sm">
        <div class="card-body">
          <form method="get" action="<?= aj360_h(aj360_url()) ?>">
            <input type="hidden" name="p" value="jobs" />

            <label class="form-label small">Department</label>
            <input name="department" class="form-control" value="<?= aj360_h($department) ?>" />

            <label class="form-label small mt-2">Category</label>
            <input name="category" class="form-control" value="<?= aj360_h($category) ?>" />

            <label class="form-label small mt-2">District</label>
            <input name="district" class="form-control" value="<?= aj360_h($district) ?>" />

            <label class="form-label small mt-2">Qualification</label>
            <input name="qualification" class="form-control" value="<?= aj360_h($qualification) ?>" />

            <div class="d-grid mt-3">
              <button class="btn btn-primary">Apply Filters</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-8">
      <div class="row g-3">
        <?php
        $mysqli = db();
        $sql = "SELECT id, job_name, department, qualification, age_limit, salary, last_date, job_slug
                FROM jobs WHERE 1=1";
        $params = [];
        $types = '';

        if ($department !== '') { $sql .= " AND department = ?"; $params[] = $department; $types .= 's'; }
        if ($category !== '') { $sql .= " AND category = ?"; $params[] = $category; $types .= 's'; }
        if ($district !== '') { $sql .= " AND district = ?"; $params[] = $district; $types .= 's'; }
        if ($qualification !== '') { $sql .= " AND qualification = ?"; $params[] = $qualification; $types .= 's'; }

        $sql .= " ORDER BY last_date ASC LIMIT 60";

        $stmt = $mysqli->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            echo '<div class="col-12"><div class="alert alert-warning mb-0">No jobs found for these filters.</div></div>';
        } else {
            while ($row = $res->fetch_assoc()) {
                echo '<div class="col-12">';
                echo '  <div class="card rounded-4 shadow-sm">';
                echo '    <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">';
                echo '      <div>'; 
                echo '        <div class="fw-semibold">'.aj360_h($row['job_name']).'</div>';
                echo '        <div class="text-muted small">'.aj360_h($row['department']).' • '.aj360_h($row['qualification']).'</div>';
                echo '        <div class="small">Age Limit: '.aj360_h($row['age_limit']).' | Salary: '.aj360_h($row['salary']).'</div>';
                echo '      </div>';
                echo '      <div class="text-md-end">';
                echo '        <div class="fw-semibold text-danger">Last Date: '.($row['last_date'] ? aj360_h($row['last_date']) : 'N/A').'</div>';
                echo '        <a class="btn btn-primary btn-sm mt-2" href="'.aj360_h(aj360_url('/', ['p' => 'job', 'job_slug' => (string)$row['job_slug']])).'">Apply / Details</a>';
                echo '      </div>';
                echo '    </div>';
                echo '  </div>';
                echo '</div>';
            }
        }
        ?>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

