<?php
declare(strict_types=1);

$metaTitle = 'Page Not Found | AssamJobs360';
$metaDescription = 'The requested page does not exist.';

ob_start();
?>
<div class="py-4">
  <div class="alert alert-warning">
    <div class="fw-semibold">404 - Not Found</div>
    <div class="small text-muted">The page you are looking for is not available.</div>
    <div class="mt-3"><a href="<?= aj360_h(aj360_url()) ?>" class="btn btn-primary btn-sm">Go to Home</a></div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

