<?php declare(strict_types=1); ?>
<?php if (basename((string)($_SERVER['SCRIPT_NAME'] ?? '')) === 'profile.php'): ?>
  <a href="<?= aj360_h(aj360_url('admin/login_activity.php')) ?>" style="position:fixed;left:16px;bottom:74px;z-index:1080;background:#1167d8;color:#fff;border-radius:8px;padding:8px 12px;font:12px Arial,sans-serif;text-decoration:none">Login Activity</a>
<?php endif; ?>
<script>
(() => {
  const timeoutMs = 60 * 60 * 1000;
  const loginUrl = <?= json_encode(aj360_url('admin/login.php?expired=1')) ?>;
  const keepaliveUrl = <?= json_encode(aj360_url('api/admin_keepalive.php')) ?>;
  const badge = document.createElement('div');
  badge.id = 'adminSessionTimer';
  badge.innerHTML = '<span>Session expires in</span><b>60:00</b>';
  badge.style.cssText = 'position:fixed;right:16px;bottom:16px;z-index:1080;background:#102a43;color:#fff;border-radius:9px;padding:9px 12px;box-shadow:0 8px 20px rgba(16,42,67,.25);font:12px Arial,sans-serif;display:flex;gap:8px;align-items:center';
  document.addEventListener('DOMContentLoaded', () => document.body.appendChild(badge));
  const clock = document.createElement('div');
  clock.id = 'adminCurrentTime';
  clock.style.cssText = 'position:fixed;left:16px;bottom:16px;z-index:1080;background:#fff;color:#102a43;border:1px solid #dce7f2;border-radius:9px;padding:8px 12px;box-shadow:0 8px 20px rgba(16,42,67,.12);font:12px Arial,sans-serif;text-align:left';
  document.addEventListener('DOMContentLoaded', () => document.body.appendChild(clock));
  let timer, remaining = timeoutMs;
  let lastPing = 0;
  const logout = () => { window.location.href = loginUrl; };
  const reset = () => {
    clearTimeout(timer);
    remaining = timeoutMs;
    timer = setTimeout(logout, timeoutMs);
    if (Date.now() - lastPing > 60000) {
      lastPing = Date.now();
      fetch(keepaliveUrl, {credentials: 'same-origin'}).catch(logout);
    }
  };
  setInterval(() => {
    remaining = Math.max(0, remaining - 1000);
    const minutes = Math.floor(remaining / 60000);
    const seconds = Math.floor((remaining % 60000) / 1000);
    badge.querySelector('b').textContent = minutes + ':' + String(seconds).padStart(2, '0');
    badge.style.background = remaining < 5 * 60000 ? '#b7472a' : '#102a43';
  }, 1000);
  const updateClock = () => {
    const now = new Date();
    clock.innerHTML = '<b style="display:block;font-size:14px">' + now.toLocaleTimeString('en-IN', {hour:'2-digit', minute:'2-digit', second:'2-digit'}) + '</b><span style="color:#64748b">' + now.toLocaleDateString('en-IN', {weekday:'short', day:'2-digit', month:'short', year:'numeric'}) + '</span>';
  };
  updateClock();
  setInterval(updateClock, 1000);
  ['click', 'keydown', 'touchstart', 'scroll'].forEach(event => document.addEventListener(event, reset, {passive: true}));
  reset();
})();
</script>
