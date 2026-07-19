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
    echo '<div class="job-detail-page py-3">';
    echo '  <div class="job-detail-hero">';
    echo '    <div class="job-detail-copy">';
    echo '      <span class="eyebrow">JOB DETAILS</span>';
    echo '      <h1>'.aj360_h($job['job_name']).'</h1>';
    echo '      <p>'.aj360_h($job['department']).' • '.aj360_h($job['category']).' • '.aj360_h($job['district']).'</p>';
    echo '      <div class="job-detail-pills">';
    echo '        <span>'.aj360_h($job['qualification']).'</span>';
    echo '        <span>'.aj360_h($job['age_limit'] ?: 'Age as per rules').'</span>';
    echo '        <span>'.aj360_h($job['salary'] ?: 'Salary as per rules').'</span>';
    echo '      </div>';
    echo '    </div>';
    echo '    <div class="job-detail-deadline">';
    echo '      <span>Last date</span>';
    echo '      <strong>'.($job['last_date'] ? aj360_h($job['last_date']) : 'TBA').'</strong>';
    echo '    </div>';
    echo '  </div>';

    echo '  <div class="row g-4 mt-1">';
    echo '    <div class="col-12 col-lg-7">';
    echo '      <div class="job-detail-panel">';
    echo '        <h2>Eligibility</h2>';
    echo '        <ul>';
    echo '          <li><span>Qualification</span><b>'.aj360_h($job['qualification']).'</b></li>';
    echo '          <li><span>Age Limit</span><b>'.aj360_h($job['age_limit'] ?: 'As per rules').'</b></li>';
    echo '          <li><span>Salary</span><b>'.aj360_h($job['salary'] ?: 'As per rules').'</b></li>';
    echo '        </ul>';
    echo '      </div>';
    echo '      <div class="job-detail-panel mt-3">';
    echo '        <h2>Important Dates</h2>';
    echo '        <div class="detail-pill detail-pill-danger">'.($job['last_date'] ? 'Last date: '.aj360_h($job['last_date']) : 'Last date: TBA').'</div>';
    echo '      </div>';
    echo '    </div>';

    echo '    <div class="col-12 col-lg-5">';
    echo '      <div class="job-detail-actions">';
    echo '        <div class="job-detail-panel">';
    echo '        <h2>Official Links</h2>';
    $applyUrl = aj360_safe_url($job['apply_url'] ?? '');
    $pdfUrl = aj360_safe_url($job['notification_pdf_url'] ?? '');
    $siteUrl = aj360_safe_url($job['official_website_url'] ?? '');

    if ($applyUrl !== '') {
        echo '  <a class="btn btn-search w-100 mb-2" target="_blank" rel="noopener noreferrer" href="'.aj360_h($applyUrl).'">Apply Online (Official)</a>';
    }
    if ($pdfUrl !== '') {
        echo '  <a class="btn btn-outline-primary w-100 mb-2" target="_blank" rel="noopener noreferrer" href="'.aj360_h($pdfUrl).'">Official Notification PDF</a>';
    }
    if ($siteUrl !== '') {
        echo '  <a class="btn btn-outline-secondary w-100" target="_blank" rel="noopener noreferrer" href="'.aj360_h($siteUrl).'">Official Website</a>';
    }

    echo '          <div class="small text-muted mt-3">Links are stored as provided by admins and open the official source in a new tab.</div>';
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
        echo '  <div class="mt-3 job-detail-panel">';
        echo '    <h2>'.aj360_h($heading).'</h2>';
        echo '    <div class="job-detail-copy-text">'.nl2br(aj360_h((string)$text)).'</div>';
        echo '  </div>';
    }
    if (!empty($job['faqs'])) {
        echo '  <div class="mt-3 job-detail-panel"><h2>FAQs</h2>';
        foreach (preg_split('/\r\n|\r|\n/', (string)$job['faqs']) ?: [] as $faq) {
            $parts = array_map('trim', explode('|', $faq, 2));
            if ($parts[0] === '') continue;
            echo '<div class="detail-faq"><div class="fw-semibold small">'.aj360_h($parts[0]).'</div>';
            if (!empty($parts[1])) echo '<div class="small text-muted">'.aj360_h($parts[1]).'</div>';
            echo '</div>';
        }
        echo '  </div>';
    }

    echo '</div>';
}

$content = ob_get_clean();
require __DIR__ . '/layout.php';

