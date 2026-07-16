<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/security.php';

$metaTitle = 'Job Details | AssamJobs360';
$metaDescription = 'Eligibility, important dates and official links.';

ob_start();

$mysqli = db();
$jobSlug = (string)($_job_slug ?? '');

$stmt = $mysqli->prepare("SELECT * FROM jobs WHERE job_slug = ? LIMIT 1");
$stmt->bind_param('s', $jobSlug);
$stmt->execute();
$res = $stmt->get_result();
$job = $res->fetch_assoc();

if (!$job) {
    http_response_code(404);
    echo '<div class="alert alert-warning">Job not found.</div>';
} else {
    echo '<div class="py-3">';
    echo '  <h1 class="h4 fw-bold">'.aj360_h($job['job_name']).'</h1>';
    echo '  <div class="text-muted small mb-3">'.aj360_h($job['department']).' • '.aj360_h($job['category']).' • '.aj360_h($job['district']).'</div>';

    echo '  <div class="row g-3">';
    echo '    <div class="col-12 col-md-7">';
    echo '      <div class="card rounded-4 shadow-sm">';
    echo '        <div class="card-body">';
    echo '          <h2 class="h6 fw-bold">Eligibility</h2>';
    echo '          <ul class="mb-0">';
    echo '            <li>Qualification: <span class="fw-semibold">'.aj360_h($job['qualification']).'</span></li>';
    echo '            <li>Age Limit: <span class="fw-semibold">'.aj360_h($job['age_limit']).'</span></li>';
    echo '            <li>Salary: <span class="fw-semibold">'.aj360_h($job['salary']).'</span></li>';
    echo '          </ul>';

    echo '          <hr />';
    echo '          <h2 class="h6 fw-bold">Important Dates</h2>';
    echo '          <div class="small">Last Date: <span class="fw-semibold text-danger">'.($job['last_date'] ? aj360_h($job['last_date']) : 'N/A').'</span></div>';
    echo '        </div>';
    echo '      </div>';
    echo '    </div>';

    echo '    <div class="col-12 col-md-5">';
    echo '      <div class="card rounded-4 shadow-sm">';
    echo '        <div class="card-body d-grid gap-2">';
    if (!empty($job['apply_url'])) {
        echo '  <a class="btn btn-primary" target="_blank" rel="noopener" href="'.aj360_h($job['apply_url']).'">Apply Online (Official)</a>';
    }
    if (!empty($job['notification_pdf_url'])) {
        echo '  <a class="btn btn-outline-primary" target="_blank" rel="noopener" href="'.aj360_h($job['notification_pdf_url']).'">Official Notification PDF</a>';
    }
    if (!empty($job['official_website_url'])) {
        echo '  <a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="'.aj360_h($job['official_website_url']).'">Official Website</a>';
    }
    echo '          <div class="small text-muted mt-1">Links are stored as provided by admins (verified where possible).</div>';
    echo '        </div>';
    echo '      </div>';
    echo '    </div>';
    echo '  </div>';

    $sections = [
        'Overview' => $job['overview'] ?? '',
        'Selection Process' => $job['selection_process'] ?? '',
        'Application Fee' => $job['application_fee'] ?? '',
        'Vacancy Details' => $job['vacancy_details'] ?? '',
        'How to Apply' => $job['how_to_apply'] ?? '',
    ];
    foreach ($sections as $heading => $text) {
        if (trim((string)$text) === '') continue;
        echo '  <div class="mt-3"><div class="card rounded-4 shadow-sm"><div class="card-body">';
        echo '    <h2 class="h6 fw-bold">'.aj360_h($heading).'</h2>';
        echo '    <div class="small text-muted mb-0">'.nl2br(aj360_h((string)$text)).'</div>';
        echo '  </div></div></div>';
    }
    if (!empty($job['faqs'])) {
        echo '  <div class="mt-3"><div class="card rounded-4 shadow-sm"><div class="card-body"><h2 class="h6 fw-bold">FAQs</h2>';
        foreach (preg_split('/\r\n|\r|\n/', (string)$job['faqs']) ?: [] as $faq) {
            $parts = array_map('trim', explode('|', $faq, 2));
            if ($parts[0] === '') continue;
            echo '<div class="border-top pt-2 mt-2"><div class="fw-semibold small">'.aj360_h($parts[0]).'</div>';
            if (!empty($parts[1])) echo '<div class="small text-muted">'.aj360_h($parts[1]).'</div>';
            echo '</div>';
        }
        echo '  </div></div></div>';
    }

    echo '</div>';
}

$content = ob_get_clean();
require __DIR__ . '/layout.php';

