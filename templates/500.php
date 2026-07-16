<?php
declare(strict_types=1);

$metaTitle = 'Server Error | AssamJobs360';
$metaDescription = 'The server encountered an error. Please try again later.';

ob_start();
?>
<div class="py-4">
  <div class="alert alert-danger">
    <div class="fw-semibold">500 - Internal Server Error</div>
    <div class="small text-muted">Something went wrong. Please try again later.</div>
    <div class="mt-3"><a href="<?= aj360_h(aj360_url()) ?>" class="btn btn-primary btn-sm">Go to Home</a></div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

