<?php
declare(strict_types=1);

$requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
$requestPath = (string)(parse_url($requestUri, PHP_URL_PATH) ?: '/');
$host = preg_replace('/[^A-Za-z0-9.:-]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$canonicalUrl = $scheme . '://' . $host . $requestPath;
$defaultSchema = ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'AssamJobs360'];
$schemaData = isset($schemaJson) && is_string($schemaJson) ? json_decode($schemaJson, true) : $defaultSchema;
if (!is_array($schemaData)) $schemaData = $defaultSchema;
$safeSchemaJson = json_encode($schemaData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="index,follow" />

  <?php
  // Security headers (defense-in-depth). Safe to set here because this is the entry template.
  if (!headers_sent()) {
      header("X-Frame-Options: DENY");
      header("X-Content-Type-Options: nosniff");
      header("Referrer-Policy: same-origin");
      header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
      // CSP: allow self + inline JSON-LD in this template.
      header("Content-Security-Policy: default-src 'self'; base-uri 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; connect-src 'self'; upgrade-insecure-requests");
  }
?>

  <title><?= isset($metaTitle) ? htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8') : 'AssamJobs360' ?></title>
  <meta name="description" content="<?= isset($metaDescription) ? htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') : 'Assam government job portal and mock tests.' ?>" />


  <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>" />

  <!-- Bootstrap 5 -->
  <link href="<?= aj360_h(aj360_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>" rel="stylesheet" />

  <!-- AssamJobs360 Theme CSS -->
  <link href="<?= aj360_h(aj360_url('assets/aj360.css')) ?>" rel="stylesheet" />



  <!-- Schema.org (basic) -->

  <script type="application/ld+json">
  <?= $safeSchemaJson ?>
  </script>

  <style>
    .sticky-top { z-index: 1030; }
  </style>

</head>
<body>
  <?php require __DIR__ . '/partials/header.php'; ?>
  <main class="container" style="padding-bottom: 72px;">
    <?= $content ?? '' ?>
  </main>

  <?php require __DIR__ . '/partials/footer.php'; ?>

  <script src="<?= aj360_h(aj360_url('assets/vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
</body>
</html>

