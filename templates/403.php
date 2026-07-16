<?php
declare(strict_types=1);

$metaTitle = 'Access Denied | AssamJobs360';
$metaDescription = 'You do not have permission to access this page.';

ob_start();
?>
<div class="py-4">
  <div class="alert alert-danger">
    <div class="fw-semibold">403 - Forbidden</div>
    <div class="small text-muted">You are not allowed to access the requested resource.</div>
    <div class="mt-3"><a href="<?= aj360_h(aj360_url()) ?>" class="btn btn-primary btn-sm">Go to Home</a></div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

