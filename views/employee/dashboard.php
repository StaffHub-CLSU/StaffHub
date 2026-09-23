<?php
use function StaffHub\Support\e;
use function StaffHub\Support\money;

$employee = $employee;
$stats = $stats;
$today = $stats['today'] ?? null;
$assetJs = (defined('APP_URL') ? rtrim(APP_URL, '/') : '') . '/assets/js';
?>
<div class="sh-page-head">
  <div>
    <div class="sh-eyebrow"><span class="sh-eyebrow-dot"></span><span>Employee Portal</span><span class="sh-eyebrow-sep">/</span><span class="sh-mono"><?= date('l, M j, Y') ?></span></div>
    <h4 class="sh-display mb-1">Welcome back, <?= e($employee['first_name']) ?>!</h4>
    <p class="sh-muted mb-0">Here's your attendance and payroll snapshot for today.</p>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-5">
    <div class="sh-punch-card h-100">
      <div class="d-flex justify-content-between align-items-start mb-3" style="position:relative; z-index:2;">
        <div>
          <div class="sh-punch-date" id="liveDate"><?= date('l, F j, Y') ?></div>
          <div class="sh-punch-clock" id="liveClock"><?= date('h:i:s A') ?></div>
        </div>
        <i class="fa-solid fa-clock fs-2" style="color: var(--sh-primary-alt); opacity:.8;"></i>
      </div>

      <div class="mb-3" style="position:relative; z-index:2;">
        <?php if ($today && $today['time_in'] && !$today['time_out']): ?>
          <span class="sh-badge sh-badge-completed">Clocked In at <?= date('h:i A', strtotime($today['time_in'])) ?></span>
        <?php elseif ($today && $today['time_out']): ?>
          <span class="sh-badge sh-badge-verified">Completed — <?= number_format((float)$today['total_hours'], 2) ?>h worked</span>
        <?php else: ?>
          <span class="sh-badge sh-badge-inactive">Not Clocked In</span>
        <?php endif; ?>
      </div>

      <div class="d-flex gap-2" style="position:relative; z-index:2;">
        <button class="btn-clock btn-clock-in flex-fill" id="clockInBtn" onclick="doClockAction('clock_in')" <?= ($today && $today['time_in']) ? 'disabled' : '' ?>>
          <i class="fa-solid fa-right-to-bracket"></i> Clock In
        </button>
        <button class="btn-clock btn-clock-out flex-fill" id="clockOutBtn" onclick="doClockAction('clock_out')" <?= (!$today || !$today['time_in'] || $today['time_out']) ? 'disabled' : '' ?>>
          <i class="fa-solid fa-right-from-bracket"></i> Clock Out
        </button>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="row g-3 h-100">
      <div class="col-md-6">
        <div class="sh-stat-card h-100">
          <div class="sh-stat-icon bg-teal-tint"><i class="fa-solid fa-calendar-check"></i></div>
          <div><div class="sh-stat-value"><?= number_format($stats['verified_hours_this_month'], 2) ?>h</div><div class="sh-stat-label">Verified Hours This Month</div></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="sh-stat-card h-100">
          <div class="sh-stat-icon bg-amber-tint"><i class="fa-solid fa-sack-dollar"></i></div>
          <div><div class="sh-stat-value"><?= $stats['last_payroll'] ? money((float)$stats['last_payroll']['net_salary']) : '—' ?></div><div class="sh-stat-label">Last Net Salary</div></div>
        </div>
      </div>
      <div class="col-12">
        <div class="sh-card p-3 h-100">
          <div class="sh-section-title mb-2">Profile Summary</div>
          <div class="row small">
            <div class="col-6 mb-2"><span class="sh-muted d-block">Employee Code</span><span class="fw-semibold sh-mono" style="color:var(--sh-primary);"><?= e($employee['employee_code']) ?></span></div>
            <div class="col-6 mb-2"><span class="sh-muted d-block">Department</span><span class="fw-semibold"><?= e($employee['department_name'] ?? '—') ?></span></div>
            <div class="col-6 mb-2"><span class="sh-muted d-block">Position</span><span class="fw-semibold"><?= e($employee['position_name'] ?? '—') ?></span></div>
            <div class="col-6 mb-2"><span class="sh-muted d-block">Employment Status</span><span class="fw-semibold"><?= e($employee['employment_status']) ?></span></div>
            <div class="col-6 mb-2"><span class="sh-muted d-block">Hourly Rate</span><span class="fw-semibold sh-mono"><?= money((float)$employee['basic_hourly_rate']) ?></span></div>
            <div class="col-6 mb-2"><span class="sh-muted d-block">Date Hired</span><span class="fw-semibold sh-mono"><?= date('M d, Y', strtotime($employee['date_hired'])) ?></span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="sh-card">
  <div class="sh-card-header-flat"><span class="sh-section-title mb-0">Recent Attendance</span></div>
  <div class="table-responsive">
    <table class="table sh-table mb-0">
      <thead><tr><th>Date</th><th>Time In</th><th>Time Out</th><th>Total Hours</th><th>Status</th></tr></thead>
      <tbody>
        <?php if (!$stats['recent_history']): ?>
          <tr><td colspan="5" class="text-center py-4 sh-muted">No attendance records yet. Clock in to get started!</td></tr>
        <?php endif; ?>
        <?php foreach ($stats['recent_history'] as $r):
          $badgeClass = $r['status'] === 'Verified' ? 'sh-badge-verified' : ($r['status'] === 'Completed' ? 'sh-badge-completed' : 'sh-badge-incomplete');
        ?>
          <tr>
            <td class="sh-mono"><?= date('M d, Y', strtotime($r['attendance_date'])) ?></td>
            <td class="sh-mono"><?= $r['time_in'] ? date('h:i A', strtotime($r['time_in'])) : '—' ?></td>
            <td class="sh-mono"><?= $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : '—' ?></td>
            <td class="sh-mono"><?= $r['total_hours'] ? number_format((float)$r['total_hours'], 2) . 'h' : '—' ?></td>
            <td><span class="sh-badge <?= $badgeClass ?>"><?= e($r['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $extraScripts = '<script src="' . $assetJs . '/employee-dashboard.js"></script>'; ?>
