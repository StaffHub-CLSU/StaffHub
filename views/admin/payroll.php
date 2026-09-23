<?php
use function StaffHub\Support\e;

$employeesList = $employeesList ?? [];
$departments = $departments ?? [];
$assetJs = (defined('APP_URL') ? rtrim(APP_URL, '/') : '') . '/assets/js';
?>
<div class="sh-page-head">
  <div>
    <div class="sh-eyebrow"><span class="sh-eyebrow-dot"></span><span>Admin Console</span><span class="sh-eyebrow-sep">/</span><span class="sh-mono"><?= date('M j, Y') ?></span></div>
    <h4 class="sh-display mb-0">Salary Processing</h4>
    <p class="sh-muted small mb-0 mt-1">Compute and process net salary from verified attendance hours.</p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <button class="btn btn-outline-primary" onclick="openBatchModal()"><i class="fa-solid fa-layer-group me-1"></i> Run Batch Payroll</button>
    <button class="btn btn-accent" onclick="openSingleModal()"><i class="fa-solid fa-calculator me-1"></i> Process Single Employee</button>
  </div>
</div>

<div class="sh-card p-3 mb-3">
  <div class="row g-2">
    <div class="col-md-4">
      <select class="form-select" id="filterPayrollEmployee">
        <option value="">All Employees</option>
        <?php foreach ($employeesList as $emp): ?><option value="<?= (int) $emp['employee_id'] ?>"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-select" id="filterPayrollDepartment">
        <option value="">All Departments</option>
        <?php foreach ($departments as $d): ?><option value="<?= (int) $d['department_id'] ?>"><?= e($d['department_name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2.5"><input type="date" class="form-control" id="filterPeriodStart" title="Period from"></div>
    <div class="col-md-2.5"><input type="date" class="form-control" id="filterPeriodEnd" title="Period to"></div>
  </div>
</div>

<div class="sh-card">
  <div class="table-responsive sh-table-scroll">
    <table class="table sh-table mb-0 align-middle">
      <thead>
        <tr><th>Employee</th><th>Period</th><th>Verified Hrs</th><th>Rate/hr</th><th>Gross</th><th>Bonuses</th><th>Deductions</th><th>Net Salary</th><th class="text-end">Actions</th></tr>
      </thead>
      <tbody id="payrollTableBody"><tr><td colspan="9" class="text-center py-4 sh-muted"><span class="spinner-border spinner-border-sm"></span></td></tr></tbody>
    </table>
  </div>
  <div class="d-flex justify-content-between align-items-center p-3 border-top">
    <span class="small sh-muted" id="payrollPaginationInfo"></span>
    <nav><ul class="pagination pagination-sm mb-0" id="payrollPagination"></ul></nav>
  </div>
</div>

<div class="modal fade sh-modal" id="singlePayrollModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Process Payroll — Single Employee</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label">Employee *</label>
            <select class="form-select" id="sp_employee_id">
              <option value="">— Select Employee —</option>
              <?php foreach ($employeesList as $emp): ?><option value="<?= (int) $emp['employee_id'] ?>"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= e($emp['employee_code']) ?>)</option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3"><label class="form-label">Period Start *</label><input type="date" class="form-control" id="sp_period_start"></div>
          <div class="col-md-3"><label class="form-label">Period End *</label><input type="date" class="form-control" id="sp_period_end"></div>
          <div class="col-md-3"><label class="form-label">Bonuses (₱)</label><input type="number" step="0.01" min="0" class="form-control" id="sp_bonuses" value="0"></div>
          <div class="col-md-3"><label class="form-label">Deductions (₱)</label><input type="number" step="0.01" min="0" class="form-control" id="sp_deductions" value="0"></div>
          <div class="col-md-6 d-flex align-items-end">
            <button class="btn btn-outline-primary w-100" onclick="previewPayroll()"><i class="fa-solid fa-eye me-1"></i> Preview Computation</button>
          </div>
        </div>

        <div id="previewBox" class="sh-card p-3 d-none" style="background: var(--sh-bg-soft);">
          <h6 class="sh-section-title mb-3">Payroll Preview</h6>
          <div class="row small">
            <div class="col-6 col-md-4 mb-2"><span class="sh-muted d-block">Employee</span><span class="fw-semibold" id="pv_name">—</span></div>
            <div class="col-6 col-md-4 mb-2"><span class="sh-muted d-block">Verified Hours</span><span class="fw-semibold" id="pv_hours">—</span></div>
            <div class="col-6 col-md-4 mb-2"><span class="sh-muted d-block">Hourly Rate</span><span class="fw-semibold" id="pv_rate">—</span></div>
            <div class="col-6 col-md-4 mb-2"><span class="sh-muted d-block">Gross Salary</span><span class="fw-semibold" id="pv_gross">—</span></div>
            <div class="col-6 col-md-4 mb-2"><span class="sh-muted d-block">Bonuses</span><span class="fw-semibold text-success" id="pv_bonuses">—</span></div>
            <div class="col-6 col-md-4 mb-2"><span class="sh-muted d-block">Deductions</span><span class="fw-semibold text-danger" id="pv_deductions">—</span></div>
          </div>
          <hr>
          <div class="d-flex justify-content-between align-items-center">
            <span class="sh-section-title mb-0">Net Salary</span>
            <span class="fs-4 fw-bold" style="color: var(--sh-primary);" id="pv_net">—</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="processSingleBtn" onclick="processSinglePayroll()" disabled>Process Payroll</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade sh-modal" id="batchPayrollModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Run Batch Payroll</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <p class="sh-muted small">Processes payroll for every active employee using their verified attendance hours in this period. Employees with zero verified hours are skipped.</p>
        <div class="row g-2">
          <div class="col-6"><label class="form-label">Period Start *</label><input type="date" class="form-control" id="bp_period_start"></div>
          <div class="col-6"><label class="form-label">Period End *</label><input type="date" class="form-control" id="bp_period_end"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="processBatchPayroll()">Run Payroll</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade sh-modal" id="payslipModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Payroll Summary</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="payslipBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Print</button>
      </div>
    </div>
  </div>
</div>

<?php $extraScripts = '<script src="' . $assetJs . '/payroll-admin.js"></script>'; ?>
