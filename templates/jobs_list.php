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
<div class="jobs-page py-3">
  <section class="jobs-hero">
    <div class="jobs-hero-copy">
      <span class="eyebrow">LATEST VACANCIES</span>
      <h1>Find Assam government jobs with less scrolling and more clarity.</h1>
      <p>Browse verified job notifications, deadlines, eligibility, and official application links in a cleaner, easier-to-read layout.</p>
      <div class="jobs-hero-badges">
        <span>Official links</span>
        <span>Deadline first</span>
        <span>Mobile friendly</span>
      </div>
    </div>
    <div class="jobs-hero-card">
      <div class="jobs-hero-stat">
        <strong>60</strong>
        <span>Latest listings per page</span>
      </div>
      <div class="jobs-hero-stat">
        <strong>4</strong>
        <span>Smart filters available</span>
      </div>
      <div class="jobs-hero-stat">
        <strong>100%</strong>
        <span>Official links highlighted</span>
      </div>
    </div>
  </section>

  <div class="row g-4 mt-1">
    <div class="col-12 col-lg-4">
      <div class="jobs-filter-card">
        <div class="section-heading mb-3">
          <div>
            <span class="eyebrow">FILTER JOBS</span>
            <h2>Refine results</h2>
          </div>
        </div>
        <form method="get" action="<?= aj360_h(aj360_url()) ?>" class="jobs-filter-form">
          <input type="hidden" name="p" value="jobs" />

          <label class="form-label small">Department</label>
          <input name="department" class="form-control" value="<?= aj360_h($department) ?>" placeholder="e.g. Police" />

          <label class="form-label small mt-3">Category</label>
          <input name="category" class="form-control" value="<?= aj360_h($category) ?>" placeholder="e.g. Group D" />

          <label class="form-label small mt-3">District</label>
          <input name="district" class="form-control" value="<?= aj360_h($district) ?>" placeholder="e.g. Guwahati" />

          <label class="form-label small mt-3">Qualification</label>
          <input name="qualification" class="form-control" value="<?= aj360_h($qualification) ?>" placeholder="e.g. HSLC" />

          <div class="d-grid mt-4">
            <button class="btn btn-search">Apply Filters</button>
          </div>
        </form>
      </div>
    </div>

    <div class="col-12 col-lg-8">
      <div class="jobs-results-head">
        <div>
          <span class="eyebrow">OPEN POSITIONS</span>
          <h2>Latest jobs</h2>
        </div>
      </div>

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
            echo '<div class="col-12"><div class="empty-jobs">No jobs found for these filters.</div></div>';
        } else {
            while ($row = $res->fetch_assoc()) {
                echo '<div class="col-12">';
                echo '  <article class="job-list-card">';
                echo '    <div class="job-list-top">';
                echo '      <div class="job-list-badges">';
                echo '        <span>'.aj360_h($row['department']).'</span>';
                echo '        <span>'.aj360_h($row['qualification']).'</span>';
                echo '      </div>';
                echo '      <div class="job-list-deadline">'.($row['last_date'] ? 'Last date: '.aj360_h($row['last_date']) : 'Last date: TBA').'</div>';
                echo '    </div>';
                echo '    <h3>'.aj360_h($row['job_name']).'</h3>';
                echo '    <div class="job-list-meta">';
                echo '      <span>Age: <b>'.aj360_h($row['age_limit'] ?: 'As per rules').'</b></span>';
                echo '      <span>Salary: <b>'.aj360_h($row['salary'] ?: 'As per rules').'</b></span>';
                echo '    </div>';
                echo '    <div class="job-list-actions">';
                echo '      <a class="btn btn-search btn-sm" href="'.aj360_h(aj360_url('/', ['p' => 'job', 'job_slug' => (string)$row['job_slug']])).'">View details</a>';
                echo '    </div>';
                echo '  </article>';
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

