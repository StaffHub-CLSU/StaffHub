<?php
$assetJs = (defined('APP_URL') ? rtrim(APP_URL, '/') : '') . '/assets/js';
?>
<div class="sh-page-head">
  <div>
    <div class="sh-eyebrow"><span class="sh-eyebrow-dot"></span><span>Admin Console</span><span class="sh-eyebrow-sep">/</span><span class="sh-mono"><?= date('M j, Y') ?></span></div>
    <h4 class="sh-display mb-0">Departments &amp; Positions</h4>
    <p class="sh-muted small mb-0 mt-1">Structure the organization — departments on the left, positions on the right.</p>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="sh-card">
      <div class="sh-card-header-flat d-flex justify-content-between align-items-center">
        <span class="sh-section-title mb-0">Departments</span>
        <button class="btn btn-sm btn-accent" onclick="openDeptModal()"><i class="fa-solid fa-plus me-1"></i>Add Department</button>
      </div>
      <div class="table-responsive">
        <table class="table sh-table mb-0">
          <thead><tr><th>Name</th><th>Description</th><th class="text-end">Actions</th></tr></thead>
          <tbody id="deptTableBody"><tr><td colspan="3" class="text-center py-4 sh-muted"><span class="spinner-border spinner-border-sm"></span></td></tr></tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="sh-card">
      <div class="sh-card-header-flat d-flex justify-content-between align-items-center">
        <span class="sh-section-title mb-0">Positions</span>
        <button class="btn btn-sm btn-accent" onclick="openPosModal()"><i class="fa-solid fa-plus me-1"></i>Add Position</button>
      </div>
      <div class="table-responsive">
        <table class="table sh-table mb-0">
          <thead><tr><th>Position</th><th>Department</th><th class="text-end">Actions</th></tr></thead>
          <tbody id="posTableBody"><tr><td colspan="3" class="text-center py-4 sh-muted"><span class="spinner-border spinner-border-sm"></span></td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade sh-modal" id="deptModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="deptForm">
        <input type="hidden" name="department_id" id="dept_id">
        <div class="modal-header"><h5 class="modal-title" id="deptModalTitle">Add Department</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div id="deptFormAlert" class="alert alert-danger py-2 small d-none"></div>
          <div class="mb-3"><label class="form-label">Department Name *</label><input class="form-control" name="department_name" required></div>
          <div class="mb-1"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade sh-modal" id="posModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="posForm">
        <input type="hidden" name="position_id" id="pos_id">
        <div class="modal-header"><h5 class="modal-title" id="posModalTitle">Add Position</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div id="posFormAlert" class="alert alert-danger py-2 small d-none"></div>
          <div class="mb-3"><label class="form-label">Position Name *</label><input class="form-control" name="position_name" required></div>
          <div class="mb-1"><label class="form-label">Department</label>
            <select class="form-select" name="department_id" id="pos_department_id"><option value="">— None —</option></select>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade sh-modal" id="shDeleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center p-4">
        <div class="mb-3"><i class="fa-solid fa-triangle-exclamation text-danger" style="font-size:40px;"></i></div>
        <h5>Delete Record?</h5>
        <p class="sh-muted">Are you sure you want to delete <strong id="shDeleteModalName"></strong>?</p>
      </div>
      <div class="modal-footer justify-content-center border-0 pb-4">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="shDeleteModalConfirm">Yes, Delete</button>
      </div>
    </div>
  </div>
</div>

<?php $extraScripts = '<script src="' . $assetJs . '/departments.js"></script>'; ?>
