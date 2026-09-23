<?php
use function StaffHub\Support\e;
use function StaffHub\Support\money;

$departments = $departments ?? [];
$rows = $rows ?? [];
$filters = $filters ?? ['department_id' => '', 'employment_status' => '', 'status' => ''];
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
    <h4 class="sh-display mb-0">Employee List</h4>
    <p class="sh-muted small mb-0 mt-1">Full employee roster with contact and employment details.</p>
  </div>
  <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> Print Report</button>
</div>

<div class="sh-card p-3 mb-3 no-print">
  <form method="GET" class="row g-2">
    <div class="col-md-4">
      <select class="form-select" name="department_id">
        <option value="">All Departments</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= $d['department_id'] ?>" <?= $filters['department_id'] == $d['department_id'] ? 'selected' : '' ?>><?= e($d['department_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <select class="form-select" name="employment_status">
        <option value="">All Employment Types</option>
        <?php foreach (['Full-Time', 'Part-Time', 'Contractual', 'Probationary'] as $s): ?>
          <option value="<?= $s ?>" <?= $filters['employment_status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-select" name="status">
        <option value="">All Status</option>
        <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
    </div>
    <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="fa-solid fa-filter"></i></button></div>
  </form>
</div>

<div class="sh-card p-4">
  <div class="d-flex justify-content-between align-items-start mb-4 border-bottom pb-3">
    <div>
      <h4 class="sh-display mb-0">StaffHub — Employee List</h4>
      <p class="sh-muted small mb-0">Generated on <?= date('F j, Y \a\t h:i A') ?> by <?= e($generatedBy) ?></p>
    </div>
    <div class="text-end small sh-muted">Total: <strong><?= count($rows) ?></strong></div>
  </div>

  <table class="table table-sm sh-table">
    <thead><tr><th>Code</th><th>Name</th><th>Department</th><th>Position</th><th>Email</th><th>Contact</th><th>Employment</th><th>Rate/hr</th><th>Date Hired</th><th>Status</th></tr></thead>
    <tbody>
      <?php if (!$rows): ?><tr><td colspan="10" class="text-center py-4 sh-muted">No employees match this filter.</td></tr><?php endif; ?>
      <?php foreach ($rows as $emp): ?>
        <tr>
          <td class="sh-mono" style="color:var(--sh-primary);"><?= e($emp['employee_code']) ?></td>
          <td><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></td>
          <td><?= e($emp['department_name'] ?? '—') ?></td>
          <td><?= e($emp['position_name'] ?? '—') ?></td>
          <td><?= e($emp['email']) ?></td>
          <td class="sh-mono"><?= e($emp['contact_number']) ?></td>
          <td><?= e($emp['employment_status']) ?></td>
          <td class="sh-mono"><?= money((float)$emp['basic_hourly_rate']) ?></td>
          <td class="sh-mono"><?= date('M d, Y', strtotime($emp['date_hired'])) ?></td>
          <td><?= $emp['is_active'] ? 'Active' : 'Inactive' ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
