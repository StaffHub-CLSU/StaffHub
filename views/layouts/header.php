<?php
/**
 * layouts/header.php — shared page shell: <head>, sidebar, topbar.
 * Expects layout vars from Controller::layoutData() (+ pageTitle, activeNav).
 */
use function StaffHub\Support\e;

$pageTitle = $pageTitle ?? APP_NAME;
$activeNav = $activeNav ?? '';
$isAdmin = (bool) ($isAdmin ?? false);
$profilePicture = $profilePicture ?? null;
$fullName = $fullName ?? 'User';
$basePath = $basePath ?? ($isAdmin ? '/admin' : '/employee');
$portalLabel = $isAdmin ? 'Admin Console' : 'Employee Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> · StaffHub</title>
<script>
(function () {
  try {
    var theme = localStorage.getItem('sh-theme');
    if (theme !== 'dark' && theme !== 'light') {
      theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
    }
    document.documentElement.setAttribute('data-theme', theme);
    document.documentElement.setAttribute('data-bs-theme', theme);
    if (localStorage.getItem('sh-sidebar-collapsed') === '1') {
      document.documentElement.setAttribute('data-sidebar', 'collapsed');
    }
  } catch (err) { /* localStorage unavailable */ }
})();
window.APP_BASE = <?= json_encode(rtrim(defined('APP_URL') ? APP_URL : '', '/'), JSON_UNESCAPED_SLASHES) ?>;
window.API_BASE = window.APP_BASE + '/api';
window.SH_UPLOAD_URL = <?= json_encode(defined('UPLOAD_URL') ? UPLOAD_URL : '', JSON_UNESCAPED_SLASHES) ?>;
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600;12..96,700;12..96,800&family=IBM+Plex+Mono:wght@400;500;600&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="<?= e((defined('APP_URL') ? rtrim(APP_URL, '/') : '') . '/assets/css/style.css?v=6') ?>" rel="stylesheet">
</head>
<body>
<div class="sh-shell">

  <!-- Sidebar -->
  <aside class="sh-sidebar" id="shSidebar">
    <div class="sh-brand sh-brand-only-logo" aria-label="StaffHub logo">
      <div class="sh-brand-mark sh-logo-icon">
        <img src="<?= e((defined('APP_URL') ? rtrim(APP_URL, '/') : '') . '/logo%20wo%20text.png') ?>" alt="StaffHub logo">
      </div>
      <div class="sh-brand-text">
        <div class="sh-brand-wordmark">StaffHub</div>
        <small><?= e($portalLabel) ?></small>
      </div>
    </div>
    <button class="sh-sidebar-toggle" id="shSidebarMiniToggle" aria-label="Open sidebar">
      <div class="sh-brand-mark sh-logo-icon">
        <img src="<?= e((defined('APP_URL') ? rtrim(APP_URL, '/') : '') . '/logo%20wo%20text.png') ?>" alt="StaffHub logo">
      </div>
    </button>

    <nav class="sh-nav">
      <?php if ($isAdmin): ?>
        <div class="sh-nav-label">Overview</div>
        <a href="/admin" class="sh-nav-link <?= $activeNav === 'dashboard' ? 'active' : '' ?>"><i class="fa-solid fa-gauge-high"></i> <span class="sh-nav-text">Dashboard</span></a>

        <div class="sh-nav-label">Workforce</div>
        <a href="/admin/employees" class="sh-nav-link <?= $activeNav === 'employees' ? 'active' : '' ?>"><i class="fa-solid fa-users"></i> <span class="sh-nav-text">Employees</span></a>
        <a href="/admin/departments" class="sh-nav-link <?= $activeNav === 'departments' ? 'active' : '' ?>"><i class="fa-solid fa-building"></i> <span class="sh-nav-text">Departments &amp; Positions</span></a>

        <div class="sh-nav-label">Timekeeping</div>
        <a href="/admin/attendance" class="sh-nav-link <?= $activeNav === 'attendance' ? 'active' : '' ?>"><i class="fa-solid fa-clock"></i> <span class="sh-nav-text">Attendance</span></a>

        <div class="sh-nav-label">Payroll</div>
        <a href="/admin/payroll" class="sh-nav-link <?= $activeNav === 'payroll' ? 'active' : '' ?>"><i class="fa-solid fa-sack-dollar"></i> <span class="sh-nav-text">Salary Processing</span></a>

        <div class="sh-nav-label">Reports</div>
        <a href="/reports/attendance" class="sh-nav-link <?= $activeNav === 'report_attendance' ? 'active' : '' ?>"><i class="fa-solid fa-file-lines"></i> <span class="sh-nav-text">Attendance Report</span></a>
        <a href="/reports/payroll" class="sh-nav-link <?= $activeNav === 'report_payroll' ? 'active' : '' ?>"><i class="fa-solid fa-file-invoice-dollar"></i> <span class="sh-nav-text">Payroll Report</span></a>
        <a href="/reports/employees" class="sh-nav-link <?= $activeNav === 'report_employees' ? 'active' : '' ?>"><i class="fa-solid fa-address-card"></i> <span class="sh-nav-text">Employee List</span></a>
      <?php else: ?>
        <div class="sh-nav-label">Overview</div>
        <a href="/employee" class="sh-nav-link <?= $activeNav === 'dashboard' ? 'active' : '' ?>"><i class="fa-solid fa-gauge-high"></i> <span class="sh-nav-text">Dashboard</span></a>

        <div class="sh-nav-label">My Records</div>
        <a href="/employee/attendance" class="sh-nav-link <?= $activeNav === 'attendance' ? 'active' : '' ?>"><i class="fa-solid fa-clock-rotate-left"></i> <span class="sh-nav-text">Attendance History</span></a>
        <a href="/employee/payroll" class="sh-nav-link <?= $activeNav === 'payroll' ? 'active' : '' ?>"><i class="fa-solid fa-money-check-dollar"></i> <span class="sh-nav-text">Payroll History</span></a>
        <a href="/employee/profile" class="sh-nav-link <?= $activeNav === 'profile' ? 'active' : '' ?>"><i class="fa-solid fa-id-badge"></i> <span class="sh-nav-text">My Profile</span></a>
      <?php endif; ?>
    </nav>

    <div class="sh-sidebar-footer">
      <a href="#" class="sh-nav-link sh-logout-link" data-logout-url="<?= e((defined('APP_URL') ? rtrim(APP_URL, '/') : '') . '/logout') ?>"><i class="fa-solid fa-right-from-bracket"></i> <span class="sh-nav-text">Logout</span></a>
    </div>
  </aside>

  <div class="sh-main">
    <!-- Topbar -->
    <div class="sh-topbar">
      <div class="d-flex align-items-center gap-3">
        <button class="sh-icon-btn" id="shSidebarToggle" aria-label="Toggle sidebar"><i class="fa-solid fa-bars"></i></button>
      </div>
      <div class="d-flex align-items-center gap-2">

        <button type="button" class="sh-icon-btn" data-theme-toggle aria-pressed="false" aria-label="Toggle dark mode" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Toggle theme">
          <i class="fa-solid fa-moon" data-theme-icon></i>
        </button>

        <div class="dropdown sh-profile-menu">
          <a href="#" class="sh-profile-trigger" data-bs-toggle="dropdown" aria-expanded="false">
            <?php if (!empty($profilePicture)): ?>
              <div id="topbarAvatar" class="sh-avatar sh-avatar-img">
                <img src="<?= e(UPLOAD_URL . $profilePicture) ?>" alt="<?= e($fullName) ?> profile">
              </div>
            <?php else: ?>
              <div id="topbarAvatar" class="sh-avatar"><?= e(strtoupper(substr($fullName, 0, 1))) ?></div>
            <?php endif; ?>
            <span class="sh-profile-trigger-text d-none d-md-flex">
              <span class="sh-profile-trigger-name"><?= e($fullName) ?></span>
              <span class="sh-profile-trigger-role"><?= $isAdmin ? 'Administrator' : 'Employee' ?></span>
            </span>
            <i class="fa-solid fa-chevron-down sh-profile-trigger-caret"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm sh-profile-dropdown">
            <li class="sh-profile-dropdown-header">
              <?php if (!empty($profilePicture)): ?>
                <div class="sh-avatar sh-avatar-img sh-avatar-lg"><img src="<?= e(UPLOAD_URL . $profilePicture) ?>" alt=""></div>
              <?php else: ?>
                <div class="sh-avatar sh-avatar-lg"><?= e(strtoupper(substr($fullName, 0, 1))) ?></div>
              <?php endif; ?>
              <div>
                <div class="sh-profile-dropdown-name"><?= e($fullName) ?></div>
                <div class="sh-profile-dropdown-role"><?= $isAdmin ? 'Administrator' : 'Employee' ?></div>
              </div>
            </li>
            <li><hr class="dropdown-divider"></li>
            <?php if (!$isAdmin): ?>
            <li><a class="dropdown-item" href="/employee/profile"><i class="fa-solid fa-id-badge"></i>Profile</a></li>
            <?php endif; ?>
            <li>
              <a class="dropdown-item sh-dropdown-theme-item" href="#" data-theme-toggle aria-pressed="false">
                <i class="fa-solid fa-moon" data-theme-icon></i><span data-theme-label>Dark Mode</span>
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item sh-dropdown-item-danger sh-logout-link" href="#" data-logout-url="<?= e((defined('APP_URL') ? rtrim(APP_URL, '/') : '') . '/logout') ?>"><i class="fa-solid fa-right-from-bracket"></i>Log Out</a></li>
          </ul>
        </div>
      </div>
    </div>

    <div class="sh-content">
