<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/security.php';

aj360_enforce_public_rate_limit();

$path = $_GET['p'] ?? 'home';

try {
    // Home page
    if ($path === 'home') {
        require __DIR__ . '/templates/home.php';
        exit;
    }

    // Jobs listing
    if ($path === 'jobs') {
        require __DIR__ . '/templates/jobs_list.php';
        exit;
    }

    // Job details (SEO-friendly via query: job_slug)
    if ($path === 'job' && !empty($_GET['job_slug'])) {
        $_job_slug = (string)$_GET['job_slug'];
        require __DIR__ . '/templates/job_details.php';
        exit;
    }

    // Placeholders for modules
    if ($path === 'admit-card') { require __DIR__ . '/templates/module_placeholder.php'; exit; }
    if ($path === 'results') { require __DIR__ . '/templates/module_placeholder.php'; exit; }
    if ($path === 'answer-key') { require __DIR__ . '/templates/module_placeholder.php'; exit; }
    if ($path === 'syllabus') { require __DIR__ . '/templates/module_placeholder.php'; exit; }
    if ($path === 'previous-papers') { require __DIR__ . '/templates/module_placeholder.php'; exit; }
    if ($path === 'mock-tests') { require __DIR__ . '/templates/mock_tests.php'; exit; }
    if ($path === 'current-affairs') { require __DIR__ . '/templates/module_placeholder.php'; exit; }

    http_response_code(404);
    require __DIR__ . '/templates/404.php';
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    require __DIR__ . '/templates/500.php';
    exit;
}


