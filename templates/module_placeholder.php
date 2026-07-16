<?php
declare(strict_types=1);

$section = $_GET['p'] ?? 'module';
$metaTitle = ucfirst(str_replace('-', ' ', (string)$section)) . ' | AssamJobs360';
$metaDescription = 'This module will be implemented step-by-step.';

ob_start();
?>
<div class="py-3">
  <div class="alert alert-info">
    <div class="fw-semibold mb-1">Coming soon: <?= htmlspecialchars($section, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="small text-muted">This is a placeholder page in the scaffold. Next step will implement dedicated listing + SEO templates.</div>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

