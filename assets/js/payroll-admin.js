/**
 * payroll-admin.js
 * Drives admin/payroll.php: filtered/paginated payroll history table,
 * the AJAX "computation preview" flow, single-employee processing,
 * batch processing, and a printable payslip view.
 */

let payrollCurrentPage = 1;
const singlePayrollModal = new bootstrap.Modal(document.getElementById('singlePayrollModal'));
const batchPayrollModal = new bootstrap.Modal(document.getElementById('batchPayrollModal'));
const payslipModal = new bootstrap.Modal(document.getElementById('payslipModal'));
let lastPreview = null;

document.addEventListener('DOMContentLoaded', () => {
  loadPayrollHistory();
  ['filterPayrollEmployee', 'filterPayrollDepartment', 'filterPeriodStart', 'filterPeriodEnd'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => { payrollCurrentPage = 1; loadPayrollHistory(); });
  });
});

async function loadPayrollHistory(page = null) {
  if (page) payrollCurrentPage = page;
  const tbody = document.getElementById('payrollTableBody');
  tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 sh-muted"><span class="spinner-border spinner-border-sm"></span></td></tr>`;

  const params = new URLSearchParams({
    action: 'list',
    page: payrollCurrentPage,
    employee_id: document.getElementById('filterPayrollEmployee').value,
    department_id: document.getElementById('filterPayrollDepartment').value,
    period_start: document.getElementById('filterPeriodStart').value,
    period_end: document.getElementById('filterPeriodEnd').value,
  });

  const data = await shAjax(`../ajax/payroll.php?${params.toString()}`);
  if (!data || !data.success) { tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-danger">Failed to load payroll history.</td></tr>`; return; }

  renderPayrollTable(data.data);
  renderPayrollPagination(data);
}

function renderPayrollTable(rows) {
  const tbody = document.getElementById('payrollTableBody');
  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="9" class="text-center py-5 sh-muted"><i class="fa-solid fa-file-invoice-dollar mb-2 d-block fs-3"></i>No payroll records yet.</td></tr>`;
    return;
  }
  tbody.innerHTML = rows.map(r => `
    <tr>
      <td><div class="fw-semibold">${escapeHtml(r.first_name)} ${escapeHtml(r.last_name)}</div><div class="sh-mono sh-muted" style="font-size:11px;">${escapeHtml(r.employee_code)}</div></td>
      <td class="sh-mono">${fmtDate(r.payroll_period_start)} – ${fmtDate(r.payroll_period_end)}</td>
      <td class="sh-mono">${parseFloat(r.verified_hours).toFixed(2)}h</td>
      <td class="sh-mono">₱${parseFloat(r.hourly_rate).toFixed(2)}</td>
      <td class="sh-mono">₱${parseFloat(r.gross_salary).toFixed(2)}</td>
      <td class="sh-mono text-success">₱${parseFloat(r.bonuses).toFixed(2)}</td>
      <td class="sh-mono text-danger">₱${parseFloat(r.deductions).toFixed(2)}</td>
      <td class="sh-mono fw-bold">₱${parseFloat(r.net_salary).toFixed(2)}</td>
      <td class="text-end"><button class="btn btn-sm btn-outline-primary" onclick="viewPayslip(${r.payroll_id})"><i class="fa-solid fa-file-invoice me-1"></i>View</button></td>
    </tr>`).join('');
}

function renderPayrollPagination(data) {
  document.getElementById('payrollPaginationInfo').textContent = `Showing ${data.data.length} of ${data.total} record(s)`;
  const pager = document.getElementById('payrollPagination');
  let html = '';
  for (let i = 1; i <= data.total_pages; i++) {
    html += `<li class="page-item ${i === data.page ? 'active' : ''}"><a class="page-link" href="#" onclick="loadPayrollHistory(${i}); return false;">${i}</a></li>`;
  }
  pager.innerHTML = html;
}

function openSingleModal() {
  document.getElementById('sp_employee_id').value = '';
  document.getElementById('sp_period_start').value = '';
  document.getElementById('sp_period_end').value = '';
  document.getElementById('sp_bonuses').value = 0;
  document.getElementById('sp_deductions').value = 0;
  document.getElementById('previewBox').classList.add('d-none');
  document.getElementById('processSingleBtn').disabled = true;
  lastPreview = null;
  singlePayrollModal.show();
}

function openBatchModal() {
  document.getElementById('bp_period_start').value = '';
  document.getElementById('bp_period_end').value = '';
  batchPayrollModal.show();
}

async function previewPayroll() {
  const employeeId = document.getElementById('sp_employee_id').value;
  const periodStart = document.getElementById('sp_period_start').value;
  const periodEnd = document.getElementById('sp_period_end').value;
  const bonuses = document.getElementById('sp_bonuses').value || 0;
  const deductions = document.getElementById('sp_deductions').value || 0;

  if (!employeeId || !periodStart || !periodEnd) {
    shToast('Please select an employee and both period dates.', 'warning');
    return;
  }

  const params = new URLSearchParams({ action: 'preview', employee_id: employeeId, period_start: periodStart, period_end: periodEnd, bonuses, deductions });
  const data = await shAjax(`../ajax/payroll.php?${params.toString()}`);
  if (!data || !data.success) {
    shToast(data?.message || 'Unable to compute preview.', 'danger');
    return;
  }

  lastPreview = data;
  document.getElementById('pv_name').textContent = `${data.employee_name} (${data.employee_code})`;
  document.getElementById('pv_hours').textContent = `${parseFloat(data.verified_hours).toFixed(2)}h`;
  document.getElementById('pv_rate').textContent = `₱${parseFloat(data.hourly_rate).toFixed(2)}`;
  document.getElementById('pv_gross').textContent = `₱${parseFloat(data.gross_salary).toFixed(2)}`;
  document.getElementById('pv_bonuses').textContent = `+ ₱${parseFloat(data.bonuses).toFixed(2)}`;
  document.getElementById('pv_deductions').textContent = `- ₱${parseFloat(data.deductions).toFixed(2)}`;
  document.getElementById('pv_net').textContent = `₱${parseFloat(data.net_salary).toFixed(2)}`;
  document.getElementById('previewBox').classList.remove('d-none');
  document.getElementById('processSingleBtn').disabled = data.verified_hours <= 0;

  if (data.verified_hours <= 0) {
    shToast('This employee has no verified attendance hours in the selected period.', 'warning');
  }
}

async function processSinglePayroll() {
  if (!lastPreview) return;
  const employeeId = document.getElementById('sp_employee_id').value;
  const periodStart = document.getElementById('sp_period_start').value;
  const periodEnd = document.getElementById('sp_period_end').value;
  const bonuses = document.getElementById('sp_bonuses').value || 0;
  const deductions = document.getElementById('sp_deductions').value || 0;

  const data = await shPost('../ajax/payroll.php', { action: 'process', employee_id: employeeId, period_start: periodStart, period_end: periodEnd, bonuses, deductions });
  if (data) {
    shToast(data.message, data.success ? 'success' : 'danger');
    if (data.success) { singlePayrollModal.hide(); loadPayrollHistory(); }
  }
}

async function processBatchPayroll() {
  const periodStart = document.getElementById('bp_period_start').value;
  const periodEnd = document.getElementById('bp_period_end').value;
  if (!periodStart || !periodEnd) { shToast('Please select both period dates.', 'warning'); return; }

  const data = await shPost('../ajax/payroll.php', { action: 'process_batch', period_start: periodStart, period_end: periodEnd });
  if (data) {
    shToast(data.message, data.success ? 'success' : 'danger');
    if (data.success) { batchPayrollModal.hide(); loadPayrollHistory(); }
  }
}

async function viewPayslip(payrollId) {
  const data = await shAjax(`../ajax/payroll.php?action=get&payroll_id=${payrollId}`);
  if (!data || !data.success) return;
  const p = data.payroll;

  document.getElementById('payslipBody').innerHTML = `
    <div class="text-center mb-3">
      <div class="fw-bold fs-5">${escapeHtml(p.first_name)} ${escapeHtml(p.last_name)}</div>
      <div class="sh-muted small">${escapeHtml(p.employee_code)} &middot; ${escapeHtml(p.department_name || '—')}</div>
      <div class="sh-muted small">Payroll Period: ${fmtDate(p.payroll_period_start)} – ${fmtDate(p.payroll_period_end)}</div>
    </div>
    <table class="table table-sm">
      <tbody>
        <tr><td class="sh-muted">Verified Hours</td><td class="text-end fw-semibold">${parseFloat(p.verified_hours).toFixed(2)}h</td></tr>
        <tr><td class="sh-muted">Hourly Rate</td><td class="text-end fw-semibold">₱${parseFloat(p.hourly_rate).toFixed(2)}</td></tr>
        <tr><td class="sh-muted">Gross Salary</td><td class="text-end fw-semibold">₱${parseFloat(p.gross_salary).toFixed(2)}</td></tr>
        <tr><td class="sh-muted">Bonuses</td><td class="text-end text-success">+ ₱${parseFloat(p.bonuses).toFixed(2)}</td></tr>
        <tr><td class="sh-muted">Deductions</td><td class="text-end text-danger">- ₱${parseFloat(p.deductions).toFixed(2)}</td></tr>
        <tr class="border-top"><td class="fw-bold">Net Salary</td><td class="text-end fw-bold fs-5">₱${parseFloat(p.net_salary).toFixed(2)}</td></tr>
      </tbody>
    </table>
    <div class="sh-muted small text-center">Processed on ${fmtDate(p.processed_date)}</div>`;

  payslipModal.show();
}

function fmtDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}
