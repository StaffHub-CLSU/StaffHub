/**
 * attendance-admin.js
 * Drives admin/attendance.php: filtered/paginated table fetch, per-row
 * verify action, and the bulk verify-by-date-range modal.
 */

let attCurrentPage = 1;
const verifyRangeModal = new bootstrap.Modal(document.getElementById('verifyRangeModal'));

document.addEventListener('DOMContentLoaded', () => {
  loadAttendance();
  ['filterEmployee', 'filterDepartment', 'filterDate', 'filterMonth', 'filterAttStatus'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => { attCurrentPage = 1; loadAttendance(); });
  });
});

async function loadAttendance(page = null) {
  if (page) attCurrentPage = page;
  const tbody = document.getElementById('attTableBody');
  tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 sh-muted"><span class="spinner-border spinner-border-sm"></span></td></tr>`;

  const params = new URLSearchParams({
    action: 'list',
    page: attCurrentPage,
    employee_id: document.getElementById('filterEmployee').value,
    department_id: document.getElementById('filterDepartment').value,
    date: document.getElementById('filterDate').value,
    month: document.getElementById('filterMonth').value,
    status: document.getElementById('filterAttStatus').value,
  });

  const data = await shAjax(`../ajax/attendance.php?${params.toString()}`);
  if (!data || !data.success) { tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Failed to load records.</td></tr>`; return; }

  renderAttTable(data.data);
  renderAttPagination(data);
}

function renderAttTable(rows) {
  const tbody = document.getElementById('attTableBody');
  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 sh-muted"><i class="fa-solid fa-calendar-xmark mb-2 d-block fs-3"></i>No attendance records found.</td></tr>`;
    return;
  }

  tbody.innerHTML = rows.map(r => `
    <tr>
      <td><div class="fw-semibold">${escapeHtml(r.first_name)} ${escapeHtml(r.last_name)}</div><div class="sh-mono sh-muted" style="font-size:11px;">${escapeHtml(r.employee_code)}</div></td>
      <td>${escapeHtml(r.department_name || '—')}</td>
      <td class="sh-mono">${r.date_fmt}</td>
      <td class="sh-mono">${r.time_in_fmt}</td>
      <td class="sh-mono">${r.time_out_fmt}</td>
      <td class="sh-mono">${r.total_hours ? parseFloat(r.total_hours).toFixed(2) + 'h' : '—'}</td>
      <td><span class="sh-badge ${r.status_badge}">${r.status}</span></td>
      <td class="text-end">
        ${r.status === 'Completed'
          ? `<button class="btn btn-sm btn-outline-primary" onclick="verifyOne(${r.attendance_id}, this)"><i class="fa-solid fa-check me-1"></i>Verify</button>`
          : `<span class="sh-muted small">—</span>`}
      </td>
    </tr>`).join('');
}

function renderAttPagination(data) {
  document.getElementById('attPaginationInfo').textContent = `Showing ${data.data.length} of ${data.total} record(s)`;
  const pager = document.getElementById('attPagination');
  let html = '';
  for (let i = 1; i <= data.total_pages; i++) {
    html += `<li class="page-item ${i === data.page ? 'active' : ''}"><a class="page-link" href="#" onclick="loadAttendance(${i}); return false;">${i}</a></li>`;
  }
  pager.innerHTML = html;
}

async function verifyOne(attendanceId, btn) {
  btn.disabled = true;
  const data = await shPost('../ajax/attendance.php', { action: 'verify', attendance_id: attendanceId });
  if (data) {
    shToast(data.message, data.success ? 'success' : 'danger');
    if (data.success) loadAttendance();
  }
  btn.disabled = false;
}

function openVerifyRangeModal() {
  document.getElementById('verifyDateFrom').value = '';
  document.getElementById('verifyDateTo').value = '';
  verifyRangeModal.show();
}

async function submitVerifyRange() {
  const dateFrom = document.getElementById('verifyDateFrom').value;
  const dateTo = document.getElementById('verifyDateTo').value;
  if (!dateFrom || !dateTo) { shToast('Please select both a start and end date.', 'warning'); return; }

  const data = await shPost('../ajax/attendance.php', { action: 'verify_range', date_from: dateFrom, date_to: dateTo });
  if (data) {
    shToast(data.message, data.success ? 'success' : 'danger');
    if (data.success) { verifyRangeModal.hide(); loadAttendance(); }
  }
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}
