<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Authentication::requireLogin('admin');

$departmentModel = new Department();
$employeeModel = new Employee();
$payrollModel = new Payroll();

$departments = $departmentModel->getAll();
$employeesList = $employeeModel->getAll([], 1, 500)['data'];

$filters = array_filter([
    'employee_id' => $_GET['employee_id'] ?? '',
    'department_id' => $_GET['department_id'] ?? '',
    'period_start' => $_GET['period_start'] ?? '',
    'period_end' => $_GET['period_end'] ?? '',
]);

$result = $payrollModel->search($filters, 1, 1000);
$rows = $result['data'];
$totalNet = array_sum(array_column($rows, 'net_salary'));
$totalGross = array_sum(array_column($rows, 'gross_salary'));

$pageTitle = 'Payroll Report';
$activeNav = 'report_payroll';
require_once __DIR__ . '/../includes/header.php';
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
    <h4 class="sh-display mb-0">Payroll Report</h4>
    <p class="sh-muted small mb-0 mt-1">Filter by employee, department, or payroll period.</p>
  </div>
  <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> Print Report</button>
</div>

<div class="sh-card p-3 mb-3 no-print">
  <form method="GET" class="row g-2">
    <div class="col-md-3">
      <select class="form-select" name="employee_id">
        <option value="">All Employees</option>
        <?php foreach ($employeesList as $emp): ?>
          <option value="<?= $emp['employee_id'] ?>" <?= ($_GET['employee_id'] ?? '') == $emp['employee_id'] ? 'selected' : '' ?>><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-select" name="department_id">
        <option value="">All Departments</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= $d['department_id'] ?>" <?= ($_GET['department_id'] ?? '') == $d['department_id'] ? 'selected' : '' ?>><?= e($d['department_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><input type="date" class="form-control" name="period_start" value="<?= e($_GET['period_start'] ?? '') ?>" title="From"></div>
    <div class="col-md-2"><input type="date" class="form-control" name="period_end" value="<?= e($_GET['period_end'] ?? '') ?>" title="To"></div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="fa-solid fa-filter me-1"></i>Filter</button></div>
  </form>
</div>

<div class="sh-card p-4">
  <div class="d-flex justify-content-between align-items-start mb-4 border-bottom pb-3">
    <div>
      <h4 class="sh-display mb-0">StaffHub — Payroll Report</h4>
      <p class="sh-muted small mb-0">Generated on <?= date('F j, Y \a\t h:i A') ?> by <?= e(Authentication::getFullName()) ?></p>
    </div>
    <div class="text-end small">
      <div class="sh-muted">Total Gross: <strong><?= money($totalGross) ?></strong></div>
      <div class="sh-muted">Total Net: <strong><?= money($totalNet) ?></strong></div>
    </div>
  </div>

  <table class="table table-sm sh-table">
    <thead><tr><th>Employee</th><th>Dept.</th><th>Period</th><th>Hrs</th><th>Rate</th><th>Gross</th><th>Bonuses</th><th>Deductions</th><th>Net</th></tr></thead>
    <tbody>
      <?php if (!$rows): ?><tr><td colspan="9" class="text-center py-4 sh-muted">No records match this filter.</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?> <span class="sh-mono sh-muted">(<?= e($r['employee_code']) ?>)</span></td>
          <td><?= e($r['department_name'] ?? '—') ?></td>
          <td class="sh-mono"><?= date('M d', strtotime($r['payroll_period_start'])) ?> – <?= date('M d, Y', strtotime($r['payroll_period_end'])) ?></td>
          <td class="sh-mono"><?= number_format((float)$r['verified_hours'], 2) ?></td>
          <td class="sh-mono"><?= money((float)$r['hourly_rate']) ?></td>
          <td class="sh-mono"><?= money((float)$r['gross_salary']) ?></td>
          <td class="sh-mono"><?= money((float)$r['bonuses']) ?></td>
          <td class="sh-mono"><?= money((float)$r['deductions']) ?></td>
          <td class="sh-mono fw-bold"><?= money((float)$r['net_salary']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
