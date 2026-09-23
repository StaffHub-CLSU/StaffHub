<?php
use function StaffHub\Support\e;

$departments = $departments ?? [];
$positions = $positions ?? [];
$assetJs = (defined('APP_URL') ? rtrim(APP_URL, '/') : '') . '/assets/js';
?>
<div class="sh-page-head">
  <div>
    <div class="sh-eyebrow"><span class="sh-eyebrow-dot"></span><span>Admin Console</span><span class="sh-eyebrow-sep">/</span><span class="sh-mono"><?= date('M j, Y') ?></span></div>
    <h4 class="sh-display mb-0">Employees</h4>
    <p class="sh-muted small mb-0 mt-1">Manage employee records, credentials, and employment status.</p>
  </div>
  <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#employeeModal" onclick="openCreateModal()">
    <i class="fa-solid fa-user-plus me-1"></i> Add Employee
  </button>
</div>

<div class="sh-card p-3 mb-3">
  <div class="row g-2">
    <div class="col-md-4">
      <div class="input-group">
        <span class="input-group-text bg-transparent"><i class="fa-solid fa-magnifying-glass sh-muted"></i></span>
        <input type="text" class="form-control" id="filterSearch" placeholder="Search name, code, or email...">
      </div>
    </div>
    <div class="col-md-3">
      <select class="form-select" id="filterDepartment">
        <option value="">All Departments</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= (int) $d['department_id'] ?>"><?= e($d['department_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-select" id="filterEmploymentStatus">
        <option value="">All Employment Types</option>
        <option value="Full-Time">Full-Time</option>
        <option value="Part-Time">Part-Time</option>
        <option value="Contractual">Contractual</option>
        <option value="Probationary">Probationary</option>
      </select>
    </div>
    <div class="col-md-2">
      <select class="form-select" id="filterStatus">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>
  </div>
</div>

<div class="sh-card">
  <div class="table-responsive sh-table-scroll">
    <table class="table sh-table mb-0 align-middle">
      <thead>
        <tr>
          <th class="sh-sortable" data-sort="name" onclick="setSort('name')">Employee <i class="fa-solid fa-sort sh-sort-icon"></i></th>
          <th class="sh-sortable" data-sort="code" onclick="setSort('code')">Code <i class="fa-solid fa-sort sh-sort-icon"></i></th>
          <th class="sh-sortable" data-sort="department" onclick="setSort('department')">Department / Position <i class="fa-solid fa-sort sh-sort-icon"></i></th>
          <th class="sh-sortable" data-sort="employment_status" onclick="setSort('employment_status')">Employment <i class="fa-solid fa-sort sh-sort-icon"></i></th>
          <th class="sh-sortable" data-sort="rate" onclick="setSort('rate')">Rate/hr <i class="fa-solid fa-sort sh-sort-icon"></i></th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody id="employeeTableBody">
        <tr><td colspan="7" class="text-center py-4 sh-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading employees...</td></tr>
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-between align-items-center p-3 border-top">
    <span class="small sh-muted" id="tablePaginationInfo"></span>
    <nav><ul class="pagination pagination-sm mb-0" id="tablePagination"></ul></nav>
  </div>
</div>

<div class="modal fade sh-modal" id="employeeModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="employeeForm">
        <input type="hidden" name="employee_id" id="f_employee_id">
        <div class="modal-header">
          <h5 class="modal-title" id="employeeModalTitle">Add Employee</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="formAlert" class="alert alert-danger py-2 small d-none"></div>

          <h6 class="sh-muted small text-uppercase fw-bold mb-2">Personal Information</h6>
          <div class="row g-3 mb-3">
            <div class="col-md-4"><label class="form-label">First Name *</label><input class="form-control" name="first_name" required></div>
            <div class="col-md-4"><label class="form-label">Last Name *</label><input class="form-control" name="last_name" required></div>
            <div class="col-md-4"><label class="form-label">Middle Name</label><input class="form-control" name="middle_name"></div>
            <div class="col-md-4"><label class="form-label">Gender *</label>
              <select class="form-select" name="gender" required>
                <option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option>
              </select>
            </div>
            <div class="col-md-4"><label class="form-label">Birthdate *</label><input type="date" class="form-control" name="birthdate" required></div>
            <div class="col-md-4"><label class="form-label">Contact Number *</label><input class="form-control" name="contact_number" required></div>
            <div class="col-12"><label class="form-label">Address</label><input class="form-control" name="address"></div>
          </div>

          <h6 class="sh-muted small text-uppercase fw-bold mb-2">Employment Details</h6>
          <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label">Department</label>
              <select class="form-select" name="department_id" id="f_department_id">
                <option value="">— Select Department —</option>
                <?php foreach ($departments as $d): ?><option value="<?= (int) $d['department_id'] ?>"><?= e($d['department_name']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Position</label>
              <select class="form-select" name="position_id" id="f_position_id">
                <option value="">— Select Position —</option>
              </select>
            </div>
            <div class="col-md-4"><label class="form-label">Employment Status *</label>
              <select class="form-select" name="employment_status" required>
                <option value="Full-Time">Full-Time</option><option value="Part-Time">Part-Time</option>
                <option value="Contractual">Contractual</option><option value="Probationary">Probationary</option>
              </select>
            </div>
            <div class="col-md-4"><label class="form-label">Basic Hourly Rate (₱) *</label><input type="number" step="0.01" min="0" class="form-control" name="basic_hourly_rate" required></div>
            <div class="col-md-4"><label class="form-label">Date Hired *</label><input type="date" class="form-control" name="date_hired" required></div>
          </div>

          <h6 class="sh-muted small text-uppercase fw-bold mb-2">Account Credentials</h6>
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Email Address *</label><input type="email" class="form-control" name="email" required></div>
            <div class="col-md-6"><label class="form-label">Username *</label><input class="form-control" name="username" required id="f_username"></div>
            <div class="col-md-6" id="passwordFieldWrap"><label class="form-label">Password *</label><input type="password" class="form-control" name="password" id="f_password"><div class="form-text">Min 8 characters, at least one letter and one number.</div></div>
            <div class="col-md-6 d-none" id="newPasswordFieldWrap"><label class="form-label">New Password (optional)</label><input type="password" class="form-control" name="new_password"><div class="form-text">Leave blank to keep current password.</div></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="employeeSaveBtn"><span class="btn-text">Save Employee</span></button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade sh-modal" id="shDeleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center p-4">
        <div class="mb-3"><i class="fa-solid fa-triangle-exclamation text-danger" style="font-size:40px;"></i></div>
        <h5>Delete Employee?</h5>
        <p class="sh-muted">Are you sure you want to permanently delete <strong id="shDeleteModalName"></strong>? This will also remove their login account, attendance, and payroll history.</p>
      </div>
      <div class="modal-footer justify-content-center border-0 pb-4">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="shDeleteModalConfirm">Yes, Delete</button>
      </div>
    </div>
  </div>
</div>

<?php $extraScripts = '<script src="' . $assetJs . '/employees.js"></script>'; ?>
