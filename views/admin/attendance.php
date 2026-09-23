<?php
use function StaffHub\Support\e;

$departments = $departments ?? [];
$employeesList = $employeesList ?? [];
$assetJs = (defined('APP_URL') ? rtrim(APP_URL, '/') : '') . '/assets/js';
?>
<div class="sh-page-head">
  <div>
    <div class="sh-eyebrow"><span class="sh-eyebrow-dot"></span><span>Admin Console</span><span class="sh-eyebrow-sep">/</span><span class="sh-mono"><?= date('M j, Y') ?></span></div>
    <h4 class="sh-display mb-0">Attendance Records</h4>
    <p class="sh-muted small mb-0 mt-1">Search, filter, and verify employee timekeeping before payroll processing.</p>
  </div>
  <button class="btn btn-accent" onclick="openVerifyRangeModal()"><i class="fa-solid fa-circle-check me-1"></i> Bulk Verify by Date Range</button>
</div>

<div class="sh-card p-3 mb-3">
  <div class="row g-2">
    <div class="col-md-3">
      <select class="form-select" id="filterEmployee">
        <option value="">All Employees</option>
        <?php foreach ($employeesList as $emp): ?>
          <option value="<?= (int) $emp['employee_id'] ?>"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-select" id="filterDepartment">
        <option value="">All Departments</option>
        <?php foreach ($departments as $d): ?><option value="<?= (int) $d['department_id'] ?>"><?= e($d['department_name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><input type="date" class="form-control" id="filterDate" title="Specific date"></div>
    <div class="col-md-2"><input type="month" class="form-control" id="filterMonth" title="Filter by month"></div>
    <div class="col-md-2">
      <select class="form-select" id="filterAttStatus">
        <option value="">All Status</option>
        <option value="Incomplete">Incomplete</option>
        <option value="Completed">Completed</option>
        <option value="Verified">Verified</option>
      </select>
    </div>
  </div>
</div>

<div class="sh-card">
  <div class="table-responsive sh-table-scroll">
    <table class="table sh-table mb-0 align-middle">
      <thead>
        <tr><th>Employee</th><th>Department</th><th>Date</th><th>Time In</th><th>Time Out</th><th>Hours</th><th>Status</th><th class="text-end">Actions</th></tr>
      </thead>
      <tbody id="attTableBody"><tr><td colspan="8" class="text-center py-4 sh-muted"><span class="spinner-border spinner-border-sm"></span></td></tr></tbody>
    </table>
  </div>
  <div class="d-flex justify-content-between align-items-center p-3 border-top">
    <span class="small sh-muted" id="attPaginationInfo"></span>
    <nav><ul class="pagination pagination-sm mb-0" id="attPagination"></ul></nav>
  </div>
</div>

<div class="modal fade sh-modal" id="verifyRangeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Bulk Verify Attendance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <p class="sh-muted small">All <strong>Completed</strong> records within this range will be marked <strong>Verified</strong> and become eligible for payroll.</p>
        <div class="row g-2">
          <div class="col-6"><label class="form-label">From</label><input type="date" class="form-control" id="verifyDateFrom"></div>
          <div class="col-6"><label class="form-label">To</label><input type="date" class="form-control" id="verifyDateTo"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="submitVerifyRange()">Verify Records</button>
      </div>
    </div>
  </div>
</div>

<?php $extraScripts = '<script src="' . $assetJs . '/attendance-admin.js"></script>'; ?>
