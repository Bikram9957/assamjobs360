<?php
declare(strict_types=1);

$metaTitle = 'Too Many Requests | AssamJobs360';
$metaDescription = 'You have sent too many requests. Please try again later.';

ob_start();
?>
<div class="py-4">
  <div class="alert alert-warning">
    <div class="fw-semibold">429 - Too Many Requests</div>
    <div class="small text-muted">Please try again shortly.</div>
    <div class="mt-3"><a href="<?= aj360_h(aj360_url()) ?>" class="btn btn-primary btn-sm">Go to Home</a></div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

