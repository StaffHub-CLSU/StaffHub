<?php
use function StaffHub\Support\e;

$departments = $departments ?? [];
$employeesList = $employeesList ?? [];
$rows = $rows ?? [];
$filters = $filters ?? ['employee_id' => '', 'department_id' => '', 'date_from' => '', 'date_to' => '', 'status' => ''];
$generatedBy = $generatedBy ?? '';
?>
<style>
  @media print {
    .sh-sidebar, .sh-topbar, .no-print { display: none !important; }
    .sh-content { padding: 0 !important; }
    .sh-shell { display: block !important; }
  }
</style>

<div class="sh-page-head no-print">
  <div>
    <div class="sh-eyebrow"><span class="sh-eyebrow-dot"></span><span>Reports</span><span class="sh-eyebrow-sep">/</span><span class="sh-mono"><?= date('M j, Y') ?></span></div>
    <h4 class="sh-display mb-0">Attendance Report</h4>
    <p class="sh-muted small mb-0 mt-1">Filter and print a copy for records or auditing.</p>
  </div>
  <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> Print Report</button>
</div>

<div class="sh-card p-3 mb-3 no-print">
  <form method="GET" class="row g-2">
    <div class="col-md-3">
      <select class="form-select" name="employee_id">
        <option value="">All Employees</option>
        <?php foreach ($employeesList as $emp): ?>
          <option value="<?= $emp['employee_id'] ?>" <?= $filters['employee_id'] == $emp['employee_id'] ? 'selected' : '' ?>><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-select" name="department_id">
        <option value="">All Departments</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= $d['department_id'] ?>" <?= $filters['department_id'] == $d['department_id'] ? 'selected' : '' ?>><?= e($d['department_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><input type="date" class="form-control" name="date_from" value="<?= e($filters['date_from']) ?>"></div>
    <div class="col-md-2"><input type="date" class="form-control" name="date_to" value="<?= e($filters['date_to']) ?>"></div>
    <div class="col-md-1">
      <select class="form-select" name="status">
        <option value="">Status</option>
        <?php foreach (['Incomplete', 'Completed', 'Verified'] as $s): ?>
          <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="fa-solid fa-filter"></i></button></div>
  </form>
</div>

<div class="sh-card p-4">
  <div class="d-flex justify-content-between align-items-start mb-4 border-bottom pb-3">
    <div>
      <h4 class="sh-display mb-0">StaffHub — Attendance Report</h4>
      <p class="sh-muted small mb-0">Generated on <?= date('F j, Y \a\t h:i A') ?> by <?= e($generatedBy) ?></p>
    </div>
    <div class="text-end small sh-muted">
      Total Records: <strong><?= count($rows) ?></strong>
    </div>
  </div>

  <table class="table table-sm sh-table">
    <thead><tr><th>Employee</th><th>Dept.</th><th>Date</th><th>Time In</th><th>Time Out</th><th>Hours</th><th>Status</th></tr></thead>
    <tbody>
      <?php if (!$rows): ?><tr><td colspan="7" class="text-center py-4 sh-muted">No records match this filter.</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?> <span class="sh-mono sh-muted">(<?= e($r['employee_code']) ?>)</span></td>
          <td><?= e($r['department_name'] ?? '—') ?></td>
          <td class="sh-mono"><?= date('M d, Y', strtotime($r['attendance_date'])) ?></td>
          <td class="sh-mono"><?= $r['time_in'] ? date('h:i A', strtotime($r['time_in'])) : '—' ?></td>
          <td class="sh-mono"><?= $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : '—' ?></td>
          <td class="sh-mono"><?= $r['total_hours'] ? number_format((float)$r['total_hours'], 2) . 'h' : '—' ?></td>
          <td><?= e($r['status']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
