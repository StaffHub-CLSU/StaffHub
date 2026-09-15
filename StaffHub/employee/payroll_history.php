<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Authentication::requireLogin('employee');

$employeeId = Authentication::getEmployeeId();
$employeeModel = new Employee();
$employee = $employeeModel->getById($employeeId);
$payrollModel = new Payroll();
$history = $payrollModel->getHistoryForEmployee($employeeId, 24);

$pageTitle = 'Payroll History';
$activeNav = 'payroll';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="sh-page-head">
  <div>
    <div class="sh-eyebrow"><span class="sh-eyebrow-dot"></span><span>Employee Portal</span><span class="sh-eyebrow-sep">/</span><span class="sh-mono"><?= date('M j, Y') ?></span></div>
    <h4 class="sh-display mb-0">My Payroll History</h4>
    <p class="sh-muted small mb-0 mt-1">View and print your past payroll summaries.</p>
  </div>
</div>

<div class="sh-card">
  <div class="table-responsive">
    <table class="table sh-table mb-0">
      <thead><tr><th>Period</th><th>Verified Hours</th><th>Rate/hr</th><th>Gross</th><th>Bonuses</th><th>Deductions</th><th>Net Salary</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$history): ?>
          <tr><td colspan="8" class="text-center py-5 sh-muted">No payroll records yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($history as $p): ?>
          <tr>
            <td class="sh-mono"><?= date('M d', strtotime($p['payroll_period_start'])) ?> – <?= date('M d, Y', strtotime($p['payroll_period_end'])) ?></td>
            <td class="sh-mono"><?= number_format((float)$p['verified_hours'], 2) ?>h</td>
            <td class="sh-mono"><?= money((float)$p['hourly_rate']) ?></td>
            <td class="sh-mono"><?= money((float)$p['gross_salary']) ?></td>
            <td class="sh-mono text-success">+<?= money((float)$p['bonuses']) ?></td>
            <td class="sh-mono text-danger">-<?= money((float)$p['deductions']) ?></td>
            <td class="sh-mono fw-bold"><?= money((float)$p['net_salary']) ?></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary" onclick='viewPayslip(<?= json_encode($p) ?>)'><i class="fa-solid fa-file-invoice me-1"></i>View</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Payslip Modal -->
<div class="modal fade sh-modal" id="payslipModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Payroll Summary</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="payslipBody">
        <div class="text-center mb-3">
          <div class="fw-bold fs-5"><?= e($employee['first_name'] . ' ' . $employee['last_name']) ?></div>
          <div class="sh-muted small"><?= e($employee['employee_code']) ?> &middot; <?= e($employee['department_name'] ?? '—') ?></div>
          <div class="sh-muted small" id="ps_period"></div>
        </div>
        <table class="table table-sm">
          <tbody>
            <tr><td class="sh-muted">Verified Hours</td><td class="text-end fw-semibold" id="ps_hours"></td></tr>
            <tr><td class="sh-muted">Hourly Rate</td><td class="text-end fw-semibold" id="ps_rate"></td></tr>
            <tr><td class="sh-muted">Gross Salary</td><td class="text-end fw-semibold" id="ps_gross"></td></tr>
            <tr><td class="sh-muted">Bonuses</td><td class="text-end text-success" id="ps_bonuses"></td></tr>
            <tr><td class="sh-muted">Deductions</td><td class="text-end text-danger" id="ps_deductions"></td></tr>
            <tr class="border-top"><td class="fw-bold">Net Salary</td><td class="text-end fw-bold fs-5" id="ps_net"></td></tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Print</button>
      </div>
    </div>
  </div>
</div>

<?php
$extraScripts = '<script>
const payslipModal = new bootstrap.Modal(document.getElementById("payslipModal"));
function viewPayslip(p) {
  document.getElementById("ps_period").textContent = new Date(p.payroll_period_start).toLocaleDateString() + " - " + new Date(p.payroll_period_end).toLocaleDateString();
  document.getElementById("ps_hours").textContent = parseFloat(p.verified_hours).toFixed(2) + "h";
  document.getElementById("ps_rate").textContent = "₱" + parseFloat(p.hourly_rate).toFixed(2);
  document.getElementById("ps_gross").textContent = "₱" + parseFloat(p.gross_salary).toFixed(2);
  document.getElementById("ps_bonuses").textContent = "+ ₱" + parseFloat(p.bonuses).toFixed(2);
  document.getElementById("ps_deductions").textContent = "- ₱" + parseFloat(p.deductions).toFixed(2);
  document.getElementById("ps_net").textContent = "₱" + parseFloat(p.net_salary).toFixed(2);
  payslipModal.show();
}
</script>';
require_once __DIR__ . '/../includes/footer.php';
?>
