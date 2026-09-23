<?php
/**
 * auth/login.php — standalone login page (no layout shell).
 */
use function StaffHub\Support\e;

$error = $error ?? '';
$fieldErrors = $fieldErrors ?? [];
$oldUsername = $oldUsername ?? '';
$assetBase = (defined('APP_URL') ? rtrim(APP_URL, '/') : '') . '/assets';
$logoUrl = (defined('APP_URL') ? rtrim(APP_URL, '/') : '') . '/logo%20wo%20text.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In · StaffHub</title>
<script>
(function () {
  try {
    var theme = localStorage.getItem('sh-theme');
    if (theme !== 'dark' && theme !== 'light') {
      theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
    }
    document.documentElement.setAttribute('data-theme', theme);
    document.documentElement.setAttribute('data-bs-theme', theme);
  } catch (err) { /* localStorage unavailable */ }
})();
window.APP_BASE = <?= json_encode(rtrim(defined('APP_URL') ? APP_URL : '', '/'), JSON_UNESCAPED_SLASHES) ?>;
window.API_BASE = window.APP_BASE + '/api';
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600;12..96,700;12..96,800&family=IBM+Plex+Mono:wght@400;500;600&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="<?= e($assetBase) ?>/css/style.css?v=6" rel="stylesheet">
</head>
<body>
<div class="sh-login-wrap">
  <button type="button" class="sh-icon-btn sh-login-theme-toggle" data-theme-toggle aria-pressed="false" aria-label="Toggle dark mode">
    <i class="fa-solid fa-moon" data-theme-icon></i>
  </button>
  <div class="sh-login-shell">

    <div class="sh-login-side d-none d-lg-flex">
      <div class="sh-login-side-brand">
        <div class="sh-brand-mark sh-logo-icon" aria-label="StaffHub logo">
          <img src="<?= e($logoUrl) ?>" alt="StaffHub logo">
        </div>
        <div>
          <div class="sh-login-side-brand-name">StaffHub</div>
          <div class="sh-login-side-brand-sub">Workforce Operating System</div>
        </div>
      </div>

      <div class="sh-login-side-content">
        <div class="sh-login-headline">Timekeeping · Attendance · Payroll</div>
        <h1 class="sh-login-welcome">Every minute,<br>accounted <em>for.</em></h1>
        <p class="sh-login-side-copy">
          Clock in, verify attendance, and run payroll from one connected
          system built for accuracy from the timesheet to the payslip.
        </p>
        <div class="sh-login-features">
          <div class="sh-login-feature"><i class="fa-solid fa-clock"></i> Smart time tracking &amp; verification</div>
          <div class="sh-login-feature"><i class="fa-solid fa-calculator"></i> Automated salary processing</div>
          <div class="sh-login-feature"><i class="fa-solid fa-chart-line"></i> Live dashboards &amp; insights</div>
        </div>
      </div>

      <div class="sh-login-clock">
        <span class="sh-login-clock-label">PH · Today</span>
        <span class="sh-login-clock-time" id="loginLiveClock"><?= date('h:i:s A') ?></span>
      </div>
    </div>

    <div class="sh-login-form-side">
      <div class="sh-login-card">
        <div class="d-lg-none text-center mb-4">
          <div class="sh-brand-mark sh-logo-icon mx-auto mb-2" style="width:64px;height:64px;" aria-label="StaffHub logo">
            <img src="<?= e($logoUrl) ?>" alt="StaffHub logo">
          </div>
        </div>
        <h3 class="sh-display mb-1" style="font-size:26px;">Sign in</h3>
        <p class="sh-muted mb-4">Welcome back. Enter your credentials to continue.</p>

        <?php if ($error): ?>
          <div class="alert alert-danger py-2 small"><i class="fa-solid fa-triangle-exclamation me-1"></i> <?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= e(rtrim(defined('APP_URL') ? APP_URL : '', '/') . '/login') ?>" novalidate id="loginForm">
          <label for="loginUsername" class="form-label">Username</label>
          <div class="sh-login-input-group">
            <i class="fa-solid fa-user sh-login-input-icon"></i>
            <input type="text" name="username" class="form-control <?= isset($fieldErrors['username']) ? 'is-invalid' : '' ?>" id="loginUsername" placeholder="Enter your username" required autofocus aria-invalid="<?= isset($fieldErrors['username']) ? 'true' : 'false' ?>" aria-describedby="loginUsernameError" value="<?= e($oldUsername) ?>">
          </div>
          <?php if (isset($fieldErrors['username'])): ?>
            <div class="invalid-feedback d-block" id="loginUsernameError">Please enter your username.</div>
          <?php endif; ?>

          <label for="loginPassword" class="form-label">Password</label>
          <div class="sh-login-input-group">
            <i class="fa-solid fa-lock sh-login-input-icon"></i>
            <input type="password" name="password" id="loginPassword" class="form-control <?= isset($fieldErrors['password']) ? 'is-invalid' : '' ?>" placeholder="Enter your password" required aria-invalid="<?= isset($fieldErrors['password']) ? 'true' : 'false' ?>" aria-describedby="loginPasswordError">
            <button type="button" class="sh-login-toggle-text" id="togglePassword">Show</button>
          </div>
          <?php if (isset($fieldErrors['password'])): ?>
            <div class="invalid-feedback d-block" id="loginPasswordError">Please enter your password.</div>
          <?php endif; ?>

          <div class="d-flex align-items-center justify-content-between mb-4 mt-1">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
              <label class="form-check-label small sh-muted" for="rememberMe">Remember me</label>
            </div>
            <a href="#" class="small fw-semibold" style="color: var(--sh-primary);">Forgot password?</a>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" style="border-radius:14px; padding:12px;">Sign In</button>
        </form>

        <div class="sh-login-divider">Demo accounts</div>

        <div class="sh-demo-chips">
          <div class="sh-demo-chip"><span>Administrator</span><code>admin · Admin@123</code></div>
          <div class="sh-demo-chip"><span>Employee</span><code>jdelacruz · Employee@123</code></div>
        </div>

        <p class="text-center small sh-muted mt-4 mb-0">
          Need access? <span class="fw-semibold">Contact your administrator.</span>
        </p>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e($assetBase) ?>/js/theme.js"></script>
<script>
document.getElementById('togglePassword').addEventListener('click', function () {
  const pw = document.getElementById('loginPassword');
  if (pw.type === 'password') { pw.type = 'text'; this.textContent = 'Hide'; }
  else { pw.type = 'password'; this.textContent = 'Show'; }
});

(function () {
  const form = document.getElementById('loginForm');
  const fields = {
    username: { input: document.getElementById('loginUsername'), errorId: 'loginUsernameError' },
    password: { input: document.getElementById('loginPassword'), errorId: 'loginPasswordError' }
  };
  const messages = {
    username: 'Please enter your username.',
    password: 'Please enter your password.'
  };

  function showError(field, hasError) {
    const { input, errorId } = fields[field];
    let msg = document.getElementById(errorId);
    input.classList.toggle('is-invalid', hasError);
    input.setAttribute('aria-invalid', hasError ? 'true' : 'false');
    if (hasError) {
      if (!msg) {
        msg = document.createElement('div');
        msg.className = 'invalid-feedback d-block';
        msg.id = errorId;
        input.closest('.sh-login-input-group').insertAdjacentElement('afterend', msg);
      }
      msg.textContent = messages[field];
    } else if (msg) {
      msg.remove();
    }
  }

  function validate(field) {
    const value = fields[field].input.value.trim();
    showError(field, value === '');
    return value !== '';
  }

  Object.keys(fields).forEach(function (field) {
    fields[field].input.addEventListener('input', function () { validate(field); });
  });

  form.addEventListener('submit', function (e) {
    let firstInvalid = null;
    Object.keys(fields).forEach(function (field) {
      if (!validate(field) && firstInvalid === null) { firstInvalid = fields[field].input; }
    });
    if (firstInvalid) {
      e.preventDefault();
      firstInvalid.focus();
    }
  });
})();
</script>
<script>
(function () {
  const el = document.getElementById('loginLiveClock');
  if (!el) return;
  const pad = (n) => String(n).padStart(2, '0');
  function tick() {
    const d = new Date();
    let h = d.getHours();
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    el.textContent = pad(h) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds()) + ' ' + ampm;
  }
  tick();
  setInterval(tick, 1000);
})();
</script>
</body>
</html>
