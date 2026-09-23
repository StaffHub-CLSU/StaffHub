<?php
use function StaffHub\Support\e;

$result = $result;
$page = $page;
$filters = $filters;
?>
<div class="sh-page-head">
  <div>
    <div class="sh-eyebrow"><span class="sh-eyebrow-dot"></span><span>Employee Portal</span><span class="sh-eyebrow-sep">/</span><span class="sh-mono"><?= date('M j, Y') ?></span></div>
    <h4 class="sh-display mb-0">My Attendance History</h4>
    <p class="sh-muted small mb-0 mt-1">A full record of your clock-ins, clock-outs, and verification status.</p>
  </div>
</div>

<div class="sh-card p-3 mb-3">
  <form method="GET" class="row g-2">
    <div class="col-md-4"><input type="date" class="form-control" name="date_from" value="<?= e($filters['date_from']) ?>"></div>
    <div class="col-md-4"><input type="date" class="form-control" name="date_to" value="<?= e($filters['date_to']) ?>"></div>
    <div class="col-md-3">
      <select class="form-select" name="status">
        <option value="">All Status</option>
        <?php foreach (['Incomplete', 'Completed', 'Verified'] as $s): ?>
          <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1"><button class="btn btn-primary w-100"><i class="fa-solid fa-filter"></i></button></div>
  </form>
</div>

<div class="sh-card">
  <div class="table-responsive">
    <table class="table sh-table mb-0">
      <thead><tr><th>Date</th><th>Time In</th><th>Time Out</th><th>Total Hours</th><th>Status</th></tr></thead>
      <tbody>
        <?php if (!$result['data']): ?>
          <tr><td colspan="5" class="text-center py-5 sh-muted">No attendance records found for this filter.</td></tr>
        <?php endif; ?>
        <?php foreach ($result['data'] as $r):
          $badgeClass = $r['status'] === 'Verified' ? 'sh-badge-verified' : ($r['status'] === 'Completed' ? 'sh-badge-completed' : 'sh-badge-incomplete');
        ?>
          <tr>
            <td class="sh-mono"><?= date('M d, Y (D)', strtotime($r['attendance_date'])) ?></td>
            <td class="sh-mono"><?= $r['time_in'] ? date('h:i A', strtotime($r['time_in'])) : '—' ?></td>
            <td class="sh-mono"><?= $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : '—' ?></td>
            <td class="sh-mono"><?= $r['total_hours'] ? number_format((float)$r['total_hours'], 2) . 'h' : '—' ?></td>
            <td><span class="sh-badge <?= $badgeClass ?>"><?= e($r['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($result['total_pages'] > 1): ?>
  <div class="d-flex justify-content-between align-items-center p-3 border-top">
    <span class="small sh-muted">Showing <?= count($result['data']) ?> of <?= $result['total'] ?> record(s)</span>
    <nav><ul class="pagination pagination-sm mb-0">
      <?php for ($i = 1; $i <= $result['total_pages']; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&date_from=<?= e($filters['date_from']) ?>&date_to=<?= e($filters['date_to']) ?>&status=<?= e($filters['status']) ?>"><?= $i ?></a></li>
      <?php endfor; ?>
    </ul></nav>
  </div>
  <?php endif; ?>
</div>
