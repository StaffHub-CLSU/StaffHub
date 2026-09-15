/**
 * departments.js
 * Drives admin/departments.php: loads/renders both tables, and wires
 * the Add/Edit modals + delete confirmation for departments & positions.
 */

const deptModal = new bootstrap.Modal(document.getElementById('deptModal'));
const posModal = new bootstrap.Modal(document.getElementById('posModal'));
let allDepartments = [];

document.addEventListener('DOMContentLoaded', () => {
  loadDepartments();
  loadPositions();
  document.getElementById('deptForm').addEventListener('submit', saveDepartment);
  document.getElementById('posForm').addEventListener('submit', savePosition);
});

async function loadDepartments() {
  const data = await shAjax('../ajax/department.php?action=list_departments');
  if (!data || !data.success) return;
  allDepartments = data.departments;

  const tbody = document.getElementById('deptTableBody');
  tbody.innerHTML = data.departments.length ? data.departments.map(d => `
    <tr>
      <td class="fw-semibold">${escapeHtml(d.department_name)}</td>
      <td class="sh-muted small">${escapeHtml(d.description || '—')}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-outline-primary" onclick='openDeptModal(${JSON.stringify(d)})'><i class="fa-solid fa-pen"></i></button>
        <button class="btn btn-sm btn-outline-danger" onclick="deleteDept(${d.department_id}, '${escapeHtml(d.department_name)}')"><i class="fa-solid fa-trash"></i></button>
      </td>
    </tr>`).join('') : `<tr><td colspan="3" class="text-center py-4 sh-muted">No departments yet.</td></tr>`;

  // populate position modal department dropdown
  const select = document.getElementById('pos_department_id');
  select.innerHTML = '<option value="">— None —</option>' + data.departments.map(d => `<option value="${d.department_id}">${escapeHtml(d.department_name)}</option>`).join('');
}

async function loadPositions() {
  const data = await shAjax('../ajax/department.php?action=list_positions');
  if (!data || !data.success) return;

  const tbody = document.getElementById('posTableBody');
  tbody.innerHTML = data.positions.length ? data.positions.map(p => `
    <tr>
      <td class="fw-semibold">${escapeHtml(p.position_name)}</td>
      <td class="sh-muted small">${escapeHtml(p.department_name || '—')}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-outline-primary" onclick='openPosModal(${JSON.stringify(p)})'><i class="fa-solid fa-pen"></i></button>
        <button class="btn btn-sm btn-outline-danger" onclick="deletePos(${p.position_id}, '${escapeHtml(p.position_name)}')"><i class="fa-solid fa-trash"></i></button>
      </td>
    </tr>`).join('') : `<tr><td colspan="3" class="text-center py-4 sh-muted">No positions yet.</td></tr>`;
}

function openDeptModal(dept = null) {
  document.getElementById('deptForm').reset();
  document.getElementById('deptFormAlert').classList.add('d-none');
  document.getElementById('dept_id').value = dept ? dept.department_id : '';
  document.getElementById('deptModalTitle').textContent = dept ? 'Edit Department' : 'Add Department';
  if (dept) {
    document.querySelector('#deptForm [name="department_name"]').value = dept.department_name;
    document.querySelector('#deptForm [name="description"]').value = dept.description || '';
  }
  deptModal.show();
}

function openPosModal(pos = null) {
  document.getElementById('posForm').reset();
  document.getElementById('posFormAlert').classList.add('d-none');
  document.getElementById('pos_id').value = pos ? pos.position_id : '';
  document.getElementById('posModalTitle').textContent = pos ? 'Edit Position' : 'Add Position';
  if (pos) {
    document.querySelector('#posForm [name="position_name"]').value = pos.position_name;
    document.querySelector('#posForm [name="department_id"]').value = pos.department_id || '';
  }
  posModal.show();
}

async function saveDepartment(e) {
  e.preventDefault();
  const formData = new FormData(e.target);
  formData.set('action', document.getElementById('dept_id').value ? 'update_department' : 'create_department');
  const data = await shAjax('../ajax/department.php', { method: 'POST', body: formData });
  if (!data) return;
  if (data.success) { shToast(data.message, 'success'); deptModal.hide(); loadDepartments(); }
  else { const a = document.getElementById('deptFormAlert'); a.textContent = data.message; a.classList.remove('d-none'); }
}

async function savePosition(e) {
  e.preventDefault();
  const formData = new FormData(e.target);
  formData.set('action', document.getElementById('pos_id').value ? 'update_position' : 'create_position');
  const data = await shAjax('../ajax/department.php', { method: 'POST', body: formData });
  if (!data) return;
  if (data.success) { shToast(data.message, 'success'); posModal.hide(); loadPositions(); }
  else { const a = document.getElementById('posFormAlert'); a.textContent = data.message; a.classList.remove('d-none'); }
}

function deleteDept(id, name) {
  shConfirmDelete(name, async () => {
    const data = await shPost('../ajax/department.php', { action: 'delete_department', department_id: id });
    if (data) { shToast(data.message, data.success ? 'success' : 'danger'); if (data.success) loadDepartments(); }
  });
}

function deletePos(id, name) {
  shConfirmDelete(name, async () => {
    const data = await shPost('../ajax/department.php', { action: 'delete_position', position_id: id });
    if (data) { shToast(data.message, data.success ? 'success' : 'danger'); if (data.success) loadPositions(); }
  });
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}
