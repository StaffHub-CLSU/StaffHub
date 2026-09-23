<?php
use function StaffHub\Support\e;

$stats = $stats ?? [];
$recentActivity = $recentActivity ?? [];
$recentClockIns = $recentClockIns ?? [];
$recentClockOuts = $recentClockOuts ?? [];
?>
<div class="sh-page-head">
  <div>
    <div class="sh-eyebrow"><span class="sh-eyebrow-dot"></span><span>Admin Console</span><span class="sh-eyebrow-sep">/</span><span class="sh-mono"><?= date('l, M j, Y') ?></span></div>
    <h4 class="sh-display mb-0">Dashboard</h4>
    <p class="sh-muted small mb-0 mt-1">Today's workforce at a glance — attendance, payroll, and activity.</p>
  </div>
</div>

<div class="row g-3 mb-4" id="statsRow">
  <div class="col-6 col-xl-2">
    <div class="sh-stat-card">
      <div class="sh-stat-icon bg-teal-tint"><i class="fa-solid fa-users"></i></div>
      <div>
        <div class="sh-stat-value" data-stat="total_employees"><?= (int) ($stats['total_employees'] ?? 0) ?></div>
        <div class="sh-stat-label">Total Employees</div>
        <div class="sh-stat-trend flat"><i class="fa-solid fa-circle-check"></i> Active workforce</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-2">
    <div class="sh-stat-card">
      <div class="sh-stat-icon bg-success-tint"><i class="fa-solid fa-user-check"></i></div>
      <div>
        <div class="sh-stat-value" data-stat="present_today"><?= (int) ($stats['present_today'] ?? 0) ?></div>
        <div class="sh-stat-label">Present Today</div>
        <div class="sh-stat-trend up"><i class="fa-solid fa-arrow-up"></i> <?= (int) ($stats['present_pct'] ?? 0) ?>% of workforce</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-2">
    <div class="sh-stat-card">
      <div class="sh-stat-icon bg-danger-tint"><i class="fa-solid fa-user-xmark"></i></div>
      <div>
        <div class="sh-stat-value" data-stat="absent_today"><?= (int) ($stats['absent_today'] ?? 0) ?></div>
        <div class="sh-stat-label">Absent Today</div>
        <div class="sh-stat-trend <?= ((int) ($stats['absent_pct'] ?? 0)) > 0 ? 'down' : 'flat' ?>"><i class="fa-solid fa-arrow-down"></i> <?= (int) ($stats['absent_pct'] ?? 0) ?>% of workforce</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-2">
    <div class="sh-stat-card">
      <div class="sh-stat-icon bg-info-tint"><i class="fa-solid fa-clock-rotate-left"></i></div>
      <div>
        <div class="sh-stat-value" data-stat="total_attendance_records"><?= (int) ($stats['total_attendance_records'] ?? 0) ?></div>
        <div class="sh-stat-label">Attendance Records</div>
        <div class="sh-stat-trend flat"><i class="fa-solid fa-database"></i> All-time total</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-2">
    <div class="sh-stat-card">
      <div class="sh-stat-icon bg-amber-tint"><i class="fa-solid fa-sack-dollar"></i></div>
      <div>
        <div class="sh-stat-value" data-stat="payrolls_processed"><?= (int) ($stats['payrolls_processed'] ?? 0) ?></div>
        <div class="sh-stat-label">Payrolls Processed</div>
        <div class="sh-stat-trend flat"><i class="fa-solid fa-file-invoice-dollar"></i> All-time total</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-2">
    <div class="sh-stat-card">
      <div class="sh-stat-icon bg-teal-tint"><i class="fa-solid fa-building"></i></div>
      <div>
        <div class="sh-stat-value" data-stat="total_departments"><?= (int) ($stats['total_departments'] ?? 0) ?></div>
        <div class="sh-stat-label">Departments</div>
        <div class="sh-stat-trend flat"><i class="fa-solid fa-sitemap"></i> Org. structure</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-5">
    <div class="sh-card p-3 h-100">
      <div class="sh-section-title">Daily Attendance (Last 7 Days)</div>
      <canvas id="chartDailyAttendance" height="200"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="sh-card p-3 h-100">
      <div class="sh-section-title">Monthly Payroll Expenses</div>
      <canvas id="chartMonthlyPayroll" height="200"></canvas>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="sh-card p-3 h-100">
      <div class="sh-section-title">Employees by Department</div>
      <canvas id="chartDeptDistribution" height="200"></canvas>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="sh-card h-100">
      <div class="sh-card-header-flat d-flex justify-content-between align-items-center">
        <span class="sh-section-title mb-0">Recent Clock-ins</span>
        <i class="fa-solid fa-right-to-bracket text-success"></i>
      </div>
      <div class="p-3" id="recentClockInsList">
        <?php if (!$recentClockIns): ?><p class="sh-muted small mb-0">No clock-ins yet today.</p><?php endif; ?>
        <?php foreach ($recentClockIns as $c): ?>
          <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
              <?php if (!empty($c['profile_picture'])): ?>
                <div class="sh-avatar sh-avatar-img">
                  <img src="<?= e(UPLOAD_URL . $c['profile_picture']) ?>" alt="<?= e($c['first_name'] . ' ' . $c['last_name']) ?>">
                </div>
              <?php else: ?>
                <div class="sh-avatar"><?= e(strtoupper(substr($c['first_name'], 0, 1) . substr($c['last_name'], 0, 1))) ?></div>
              <?php endif; ?>
              <div>
                <div class="fw-semibold small"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></div>
                <div class="sh-muted" style="font-size:12px;"><?= e($c['employee_code']) ?></div>
              </div>
            </div>
            <span class="sh-mono sh-muted small"><?= date('h:i A', strtotime($c['time_in'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="sh-card h-100">
      <div class="sh-card-header-flat d-flex justify-content-between align-items-center">
        <span class="sh-section-title mb-0">Recent Clock-outs</span>
        <i class="fa-solid fa-right-from-bracket text-danger"></i>
      </div>
      <div class="p-3" id="recentClockOutsList">
        <?php if (!$recentClockOuts): ?><p class="sh-muted small mb-0">No clock-outs yet today.</p><?php endif; ?>
        <?php foreach ($recentClockOuts as $c): ?>
          <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
              <?php if (!empty($c['profile_picture'])): ?>
                <div class="sh-avatar sh-avatar-img">
                  <img src="<?= e(UPLOAD_URL . $c['profile_picture']) ?>" alt="<?= e($c['first_name'] . ' ' . $c['last_name']) ?>">
                </div>
              <?php else: ?>
                <div class="sh-avatar"><?= e(strtoupper(substr($c['first_name'], 0, 1) . substr($c['last_name'], 0, 1))) ?></div>
              <?php endif; ?>
              <div>
                <div class="fw-semibold small"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></div>
                <div class="sh-muted" style="font-size:12px;"><?= e($c['employee_code']) ?></div>
              </div>
            </div>
            <span class="sh-mono sh-muted small"><?= date('h:i A', strtotime($c['time_out'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="sh-card h-100">
      <div class="sh-card-header-flat d-flex justify-content-between align-items-center">
        <span class="sh-section-title mb-0">Recent Activity</span>
        <i class="fa-solid fa-list-check sh-muted"></i>
      </div>
      <div class="p-3" id="recentActivityList" style="max-height:280px; overflow-y:auto;">
        <?php foreach ($recentActivity as $log): ?>
          <div class="d-flex gap-2 py-2 border-bottom">
            <i class="fa-solid fa-circle-dot mt-1" style="font-size:6px; color: var(--sh-primary-alt);"></i>
            <div>
              <div class="small"><span class="fw-semibold"><?= e($log['username'] ?? 'System') ?></span> <?= e($log['activity']) ?></div>
              <div class="sh-muted" style="font-size:11px;"><?= date('M d, h:i A', strtotime($log['timestamp'])) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<?php
$assetJs = (defined('APP_URL') ? rtrim(APP_URL, '/') : '') . '/assets/js';
$extraScripts = '<script>
const dailyAttendanceData = ' . json_encode($dailyAttendance ?? ['labels' => [], 'values' => []]) . ';
const monthlyPayrollData = ' . json_encode($monthlyPayroll ?? ['labels' => [], 'values' => []]) . ';
const deptDistributionData = ' . json_encode($deptDistribution ?? ['labels' => [], 'values' => []]) . ';
</script><script src="' . $assetJs . '/dashboard.js"></script>';
