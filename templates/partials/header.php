<?php declare(strict_types=1); ?>
<div class="top-strip"><div class="container"><span><b>NEW</b> Latest Assam government job notifications are updated daily</span><a href="<?= aj360_h(aj360_url('/', ['p' => 'jobs'])) ?>">Browse jobs →</a></div></div>
<header class="site-header sticky-top">

  <div class="container py-2">
    <div class="d-flex align-items-center justify-content-between">
      <a href="<?= aj360_h(aj360_url()) ?>" class="brand">
        AssamJobs360 <span class="text-success">•</span> <span class="text-primary">Jobs</span>
      </a>


      <nav class="main-nav d-none d-md-flex gap-4">
        <a class="text-decoration-none" href="<?= aj360_h(aj360_url('/', ['p' => 'jobs'])) ?>">Latest Jobs</a>
        <a class="text-decoration-none" href="<?= aj360_h(aj360_url('/', ['p' => 'mock-tests'])) ?>">Mock Tests</a>
        <a class="text-decoration-none" href="<?= aj360_h(aj360_url('/', ['p' => 'current-affairs'])) ?>">Current Affairs</a>

        <?php if (!empty($_SESSION['aj360_user_id'])): ?>
          <a class="text-decoration-none" href="<?= aj360_h(aj360_url('user/profile.php')) ?>">Profile</a>
          <a class="text-decoration-none" href="<?= aj360_h(aj360_url('user/logout.php')) ?>">Logout</a>
        <?php else: ?>
          <a class="text-decoration-none" href="<?= aj360_h(aj360_url('user/login.php')) ?>">Login</a>
          <a class="text-decoration-none" href="<?= aj360_h(aj360_url('user/register.php')) ?>">Register</a>
        <?php endif; ?>

        <?php if (!empty($_SESSION['aj360_admin_id'])): ?>
          <a class="text-decoration-none admin-link" href="<?= aj360_h(aj360_url('admin/')) ?>">Admin</a>
        <?php endif; ?>
      </nav>


      <button class="menu-toggle d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#aj360Offcanvas">
        ☰
      </button>
    </div>
  </div>

  <div class="offcanvas offcanvas-end" tabindex="-1" id="aj360Offcanvas">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title">AssamJobs360</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-grid gap-3">
      <a class="text-decoration-none" href="<?= aj360_h(aj360_url('/', ['p' => 'jobs'])) ?>">Latest Jobs</a>
      <a class="text-decoration-none" href="<?= aj360_h(aj360_url('/', ['p' => 'mock-tests'])) ?>">Mock Tests</a>
      <a class="text-decoration-none" href="<?= aj360_h(aj360_url('/', ['p' => 'current-affairs'])) ?>">Current Affairs</a>

      <?php if (!empty($_SESSION['aj360_user_id'])): ?>
        <a class="text-decoration-none" href="<?= aj360_h(aj360_url('user/profile.php')) ?>">Profile</a>
        <a class="text-decoration-none" href="<?= aj360_h(aj360_url('user/logout.php')) ?>">Logout</a>
      <?php else: ?>
        <a class="text-decoration-none" href="<?= aj360_h(aj360_url('user/login.php')) ?>">Login</a>
        <a class="text-decoration-none" href="<?= aj360_h(aj360_url('user/register.php')) ?>">Register</a>
      <?php endif; ?>

      <?php if (!empty($_SESSION['aj360_admin_id'])): ?>
        <a class="text-decoration-none admin-link" href="<?= aj360_h(aj360_url('admin/')) ?>">Admin</a>
      <?php endif; ?>
    </div>

  </div>
</header>

