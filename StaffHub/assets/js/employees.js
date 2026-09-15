/**
 * employees.js
 * Drives admin/employees.php: debounced live search, filters, pagination,
 * add/edit modal, delete confirmation, and status toggling — all via
 * ajax/employee.php without full page reloads.
 */

let currentPage = 1;
let currentSortBy = '';
let currentSortDir = 'desc';
let debounceTimer = null;
const employeeModalEl = document.getElementById('employeeModal');
const employeeModal = new bootstrap.Modal(employeeModalEl);

document.addEventListener('DOMContentLoaded', () => {
  loadEmployees();

  document.getElementById('filterSearch').addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => { currentPage = 1; loadEmployees(); }, 350);
  });
  ['filterDepartment', 'filterEmploymentStatus', 'filterStatus'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => { currentPage = 1; loadEmployees(); });
  });

  document.getElementById('f_department_id').addEventListener('change', (e) => loadPositions(e.target.value));

  document.getElementById('employeeForm').addEventListener('submit', handleSaveEmployee);
});

async function loadEmployees(page = null) {
  if (page) currentPage = page;
  const tbody = document.getElementById('employeeTableBody');
  tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 sh-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>`;

  const params = new URLSearchParams({
    action: 'list',
    page: currentPage,
    search: document.getElementById('filterSearch').value,
    department_id: document.getElementById('filterDepartment').value,
    employment_status: document.getElementById('filterEmploymentStatus').value,
    status: document.getElementById('filterStatus').value,
    sort_by: currentSortBy,
    sort_dir: currentSortDir,
  });

  const data = await shAjax(`../ajax/employee.php?${params.toString()}`);
  if (!data || !data.success) { tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Failed to load employees.</td></tr>`; return; }

  renderTable(data.data);
  renderPagination(data);
  updateSortIcons();
}

/** Toggles sort direction on repeat clicks of the same column, else defaults to ascending. */
function setSort(column) {
  if (currentSortBy === column) {
    currentSortDir = currentSortDir === 'asc' ? 'desc' : 'asc';
  } else {
    currentSortBy = column;
    currentSortDir = 'asc';
  }
  currentPage = 1;
  loadEmployees();
}

function updateSortIcons() {
  document.querySelectorAll('.sh-sortable').forEach(th => {
    const icon = th.querySelector('.sh-sort-icon');
    const col = th.getAttribute('data-sort');
    th.classList.remove('sh-sort-active');
    icon.className = 'fa-solid fa-sort sh-sort-icon';
    if (col === currentSortBy) {
      th.classList.add('sh-sort-active');
      icon.className = `fa-solid ${currentSortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down'} sh-sort-icon`;
    }
  });
}

function renderTable(rows) {
  const tbody = document.getElementById('employeeTableBody');
  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 sh-muted"><i class="fa-solid fa-users-slash mb-2 d-block fs-3"></i>No employees found.</td></tr>`;
    return;
  }

  tbody.innerHTML = rows.map(emp => {
    const initials = (emp.first_name[0] || '') + (emp.last_name[0] || '');
    const uploadBase = window.SH_UPLOAD_URL ? window.SH_UPLOAD_URL.replace(/\/$/, '') + '/' : '../uploads/profile_pictures/';
    const avatar = emp.profile_picture
      ? `<div class="sh-avatar sh-avatar-img"><img src="${uploadBase}${escapeHtml(emp.profile_picture)}" alt="${escapeHtml(emp.first_name)} ${escapeHtml(emp.last_name)}"></div>`
      : `<div class="sh-avatar">${initials.toUpperCase()}</div>`;
    const statusBadge = emp.is_active == 1
      ? `<span class="sh-badge sh-badge-active">Active</span>`
      : `<span class="sh-badge sh-badge-inactive">Inactive</span>`;

    return `
      <tr>
        <td>
          <div class="d-flex align-items-center gap-2">
            ${avatar}
            <div>
              <div class="fw-semibold">${escapeHtml(emp.first_name)} ${escapeHtml(emp.last_name)}</div>
              <div class="sh-muted" style="font-size:12px;">${escapeHtml(emp.email)}</div>
            </div>
          </div>
        </td>
        <td><code class="sh-mono" style="color:var(--sh-primary);">${escapeHtml(emp.employee_code)}</code></td>
        <td>
          <div>${escapeHtml(emp.department_name || '—')}</div>
          <div class="sh-muted" style="font-size:12px;">${escapeHtml(emp.position_name || '—')}</div>
        </td>
        <td>${escapeHtml(emp.employment_status)}</td>
        <td class="sh-mono">₱${parseFloat(emp.basic_hourly_rate).toFixed(2)}</td>
        <td>${statusBadge}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-primary" title="Edit" onclick="openEditModal(${emp.employee_id})"><i class="fa-solid fa-pen"></i></button>
          <button class="btn btn-sm btn-outline-secondary" title="${emp.is_active == 1 ? 'Deactivate' : 'Activate'}" onclick="toggleStatus(${emp.employee_id}, ${emp.is_active == 1 ? 0 : 1})">
            <i class="fa-solid ${emp.is_active == 1 ? 'fa-user-slash' : 'fa-user-check'}"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteEmployee(${emp.employee_id}, '${escapeHtml(emp.first_name)} ${escapeHtml(emp.last_name)}')"><i class="fa-solid fa-trash"></i></button>
        </td>
      </tr>`;
  }).join('');
}

function renderPagination(data) {
  document.getElementById('tablePaginationInfo').textContent =
    `Showing ${data.data.length} of ${data.total} employee(s)`;

  const pager = document.getElementById('tablePagination');
  let html = '';
  for (let i = 1; i <= data.total_pages; i++) {
    html += `<li class="page-item ${i === data.page ? 'active' : ''}"><a class="page-link" href="#" onclick="loadEmployees(${i}); return false;">${i}</a></li>`;
  }
  pager.innerHTML = html;
}

async function loadPositions(departmentId, selectedId = '') {
  const select = document.getElementById('f_position_id');
  select.innerHTML = `<option value="">— Select Position —</option>`;
  const data = await shAjax(`../ajax/search.php?action=positions_by_department&department_id=${departmentId || ''}`);
  if (data && data.success) {
    data.positions.forEach(p => {
      const opt = document.createElement('option');
      opt.value = p.position_id;
      opt.textContent = p.position_name;
      if (String(p.position_id) === String(selectedId)) opt.selected = true;
      select.appendChild(opt);
    });
  }
}

function openCreateModal() {
  document.getElementById('employeeForm').reset();
  document.getElementById('f_employee_id').value = '';
  document.getElementById('employeeModalTitle').textContent = 'Add Employee';
  document.getElementById('f_username').removeAttribute('disabled');
  document.getElementById('f_password').setAttribute('required', 'required');
  document.getElementById('passwordFieldWrap').classList.remove('d-none');
  document.getElementById('newPasswordFieldWrap').classList.add('d-none');
  document.getElementById('formAlert').classList.add('d-none');
  loadPositions('');
}

async function openEditModal(employeeId) {
  const data = await shAjax(`../ajax/employee.php?action=get&employee_id=${employeeId}`);
  if (!data || !data.success) return;

  const emp = data.employee;
  const form = document.getElementById('employeeForm');
  form.reset();
  document.getElementById('formAlert').classList.add('d-none');
  document.getElementById('employeeModalTitle').textContent = `Edit ${emp.first_name} ${emp.last_name}`;
  document.getElementById('f_employee_id').value = emp.employee_id;

  form.first_name.value = emp.first_name;
  form.last_name.value = emp.last_name;
  form.middle_name.value = emp.middle_name || '';
  form.gender.value = emp.gender;
  form.birthdate.value = emp.birthdate;
  form.contact_number.value = emp.contact_number;
  form.address.value = emp.address || '';
  form.department_id.value = emp.department_id || '';
  form.employment_status.value = emp.employment_status;
  form.basic_hourly_rate.value = emp.basic_hourly_rate;
  form.date_hired.value = emp.date_hired;
  form.email.value = emp.email;
  form.username.value = emp.username;

  document.getElementById('f_username').setAttribute('disabled', 'disabled');
  document.getElementById('f_password').removeAttribute('required');
  document.getElementById('passwordFieldWrap').classList.add('d-none');
  document.getElementById('newPasswordFieldWrap').classList.remove('d-none');

  await loadPositions(emp.department_id || '', emp.position_id);
  employeeModal.show();
}

async function handleSaveEmployee(e) {
  e.preventDefault();
  const form = e.target;
  const isEdit = !!document.getElementById('f_employee_id').value;
  const formData = new FormData(form);
  formData.set('action', isEdit ? 'update' : 'create');

  // Re-enable username field value even if disabled (disabled inputs aren't submitted)
  if (isEdit) formData.set('username', document.getElementById('f_username').value);

  const btn = document.getElementById('employeeSaveBtn');
  btn.disabled = true;
  btn.querySelector('.btn-text').innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

  const data = await shAjax('../ajax/employee.php', { method: 'POST', body: formData });

  btn.disabled = false;
  btn.querySelector('.btn-text').textContent = 'Save Employee';

  if (!data) return;

  if (data.success) {
    shToast(data.message, 'success');
    employeeModal.hide();
    loadEmployees();
  } else {
    const alertBox = document.getElementById('formAlert');
    alertBox.textContent = data.message || 'Please correct the errors and try again.';
    alertBox.classList.remove('d-none');
  }
}

function deleteEmployee(employeeId, name) {
  shConfirmDelete(name, async () => {
    const data = await shPost('../ajax/employee.php', { action: 'delete', employee_id: employeeId });
    if (data && data.success) {
      shToast(data.message, 'success');
      loadEmployees();
    }
  });
}

async function toggleStatus(employeeId, active) {
  const data = await shPost('../ajax/employee.php', { action: 'toggle_status', employee_id: employeeId, active });
  if (data && data.success) {
    shToast(data.message, 'success');
    loadEmployees();
  }
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}
