/**
 * app.js
 * Shared front-end utilities used across every StaffHub page:
 *  - shToast()          : Bootstrap toast notifications for AJAX feedback
 *  - shAjax()            : fetch() wrapper with JSON handling + error toasts
 *  - sidebar toggle for mobile
 *  - generic delete-confirmation modal wiring
 */

/** Shows an Apple-style notification banner. type: 'success' | 'danger' | 'warning' | 'info' */
function shToast(message, type = 'success') {
  const container = document.getElementById('shToastContainer');
  if (!container) { alert(message); return; }

  const icons = {
    success: 'fa-circle-check',
    danger: 'fa-circle-exclamation',
    warning: 'fa-triangle-exclamation',
    info: 'fa-circle-info',
  };
  const tints = {
    success: 'var(--sh-success)',
    danger: 'var(--sh-danger)',
    warning: 'var(--sh-warning)',
    info: 'var(--sh-info)',
  };
  const tintBg = {
    success: 'var(--sh-success-tint)',
    danger: 'var(--sh-danger-tint)',
    warning: 'var(--sh-warning-tint)',
    info: 'var(--sh-info-tint)',
  };
  const color = tints[type] || tints.info;
  const bg = tintBg[type] || tintBg.info;

  const el = document.createElement('div');
  el.className = 'toast align-items-center border-0';
  el.setAttribute('role', 'alert');
  el.innerHTML = `
    <div class="d-flex align-items-center">
      <div class="toast-body">
        <span class="d-inline-flex align-items-center justify-content-center flex-shrink-0"
              style="width:32px;height:32px;border-radius:10px;background:${bg};color:${color};margin-right:12px;">
          <i class="fa-solid ${icons[type] || icons.info}"></i>
        </span>
        <span>${message}</span>
      </div>
      <button type="button" class="btn-close me-3 ms-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>`;
  container.appendChild(el);

  const toast = new bootstrap.Toast(el, { delay: 4200 });
  el.addEventListener('show.bs.toast', () => {
    const closeBtn = el.querySelector('.btn-close');
    if (closeBtn) closeBtn.addEventListener('click', () => el.classList.add('hiding'));
  });
  toast.show();
  setTimeout(() => el.classList.add('hiding'), 3600);
  el.addEventListener('hidden.bs.toast', () => el.remove());
}

/**
 * fetch() wrapper. Always sends/expects JSON. Automatically shows a toast
 * on network-level failures so callers only need to handle the happy path.
 */
async function shAjax(url, options = {}) {
  try {
    const response = await fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest', ...(options.headers || {}) },
      ...options,
    });

    if (response.status === 401) {
      shToast('Your session has expired. Please log in again.', 'warning');
      setTimeout(() => (window.location.href = '../index.php'), 1500);
      return null;
    }

    const contentType = response.headers.get('content-type') || '';
    const data = contentType.includes('application/json') ? await response.json() : await response.text();

    if (!response.ok && typeof data === 'object' && data?.message) {
      shToast(data.message, 'danger');
    }
    return data;
  } catch (err) {
    console.error('shAjax error:', err);
    shToast('A network error occurred. Please try again.', 'danger');
    return null;
  }
}

/** POST helper that automatically encodes a plain object as FormData. */
async function shPost(url, payload = {}) {
  const formData = new FormData();
  Object.entries(payload).forEach(([key, value]) => formData.append(key, value ?? ''));
  return shAjax(url, { method: 'POST', body: formData });
}

/**
 * Sidebar collapsed/expanded state persistence.
 *
 * The sidebar's open/closed state is completely separate from navigation.
 * Every page load is a fresh HTML page (this is a classic multi-page PHP
 * app, not a single-page app), so "remembering" the state across page
 * clicks means saving it to localStorage and re-applying it on every load.
 * The actual re-apply happens as early as possible via an inline snippet
 * in <head> (see includes/header.php) so the sidebar never "flashes" open
 * before collapsing. This block only needs to keep that storage updated
 * and keep the mini-toggle in sync after that early snippet has run.
 */
const SH_SIDEBAR_KEY = 'sh-sidebar-collapsed';

function shSaveSidebarState(collapsed) {
  try { localStorage.setItem(SH_SIDEBAR_KEY, collapsed ? '1' : '0'); } catch (err) { /* storage unavailable, ignore */ }
}

/**
 * Collapses or expands the sidebar. The state lives on <html data-sidebar>
 * (not a class on .sh-shell) because the very first inline script in <head>
 * — which runs before the page has a <body> to query — sets that same
 * attribute from localStorage to prevent a flash of the wrong state. Every
 * other bit of code (this file, style.css) just follows that one flag.
 */
function shSetSidebarCollapsed(collapsed) {
  document.documentElement.setAttribute('data-sidebar', collapsed ? 'collapsed' : 'expanded');
  shSaveSidebarState(collapsed);
}

document.addEventListener('DOMContentLoaded', () => {
  // Mobile sidebar toggle
  const toggleBtn = document.getElementById('shSidebarToggle');
  const sidebar = document.getElementById('shSidebar');
  if (toggleBtn && sidebar) {
    const shell = document.querySelector('.sh-shell');
    const mobileBreakpoint = 992;

    // Reflect whatever state the early <head> snippet already applied
    // (read straight from localStorage, since that's the source of truth).
    const startedCollapsed = document.documentElement.getAttribute('data-sidebar') === 'collapsed';
    toggleBtn.setAttribute('aria-expanded', (!startedCollapsed).toString());

    toggleBtn.addEventListener('click', () => {
      if (window.innerWidth < mobileBreakpoint) {
        // On small screens the sidebar is an overlay drawer; this open/close
        // state is intentionally NOT persisted (it always starts closed).
        sidebar.classList.toggle('show');
        toggleBtn.setAttribute('aria-expanded', sidebar.classList.contains('show'));
      } else if (shell) {
        const collapsed = document.documentElement.getAttribute('data-sidebar') !== 'collapsed';
        shSetSidebarCollapsed(collapsed);
        toggleBtn.setAttribute('aria-expanded', (!collapsed).toString());
        // ensure mini toggle visibility updates are reachable
        const mini = document.getElementById('shSidebarMiniToggle');
        if (mini) mini.setAttribute('aria-hidden', collapsed ? 'false' : 'true');
      }
    });

    document.addEventListener('click', (e) => {
      if (window.innerWidth < mobileBreakpoint && sidebar.classList.contains('show') &&
          !sidebar.contains(e.target) && e.target !== toggleBtn && !toggleBtn.contains(e.target)) {
        sidebar.classList.remove('show');
      }
    });
    // Compact sidebar mini-toggle (logo rail) — allows opening when collapsed
    const miniToggle = document.getElementById('shSidebarMiniToggle');
    if (miniToggle && shell) {
      miniToggle.addEventListener('click', () => {
        const collapsed = document.documentElement.getAttribute('data-sidebar') !== 'collapsed';
        shSetSidebarCollapsed(collapsed);
        // mirror aria-expanded on main toggle if present
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', (!collapsed).toString());
        miniToggle.setAttribute('aria-expanded', (!collapsed).toString());
      });
    }

    // IMPORTANT: navigation links inside the sidebar must never change its
    // open/closed state — clicking "Attendance" while collapsed just
    // navigates there and the next page loads collapsed too (handled by the
    // early inline snippet in <head>, which reads the same SH_SIDEBAR_KEY).
    // No extra code is needed here to enforce that; we simply never toggle
    // the class from a nav-link click.
  }

  // Preview hook: collapse the sidebar automatically when ?preview_sidebar=1 is present in the URL
  try {
    const url = new URL(window.location.href);
    if (url.searchParams.get('preview_sidebar') === '1') {
      const shell = document.querySelector('.sh-shell');
      const mobileBreakpoint = 992;
      if (window.innerWidth >= mobileBreakpoint) {
          shSetSidebarCollapsed(true);
          if (sidebar) sidebar.classList.remove('show');
          // update toggle aria if present
          const tb = document.getElementById('shSidebarToggle');
          if (tb) tb.setAttribute('aria-expanded', 'false');
        }
    }
  } catch (err) { /* ignore URL parsing errors */ }

  // Logout confirmation modal
  const logoutLinks = document.querySelectorAll('.sh-logout-link');
  const logoutModalEl = document.getElementById('shLogoutModal');
  const logoutConfirmBtn = document.getElementById('shLogoutConfirmBtn');
  if (logoutLinks.length && logoutModalEl && logoutConfirmBtn) {
    const logoutModal = new bootstrap.Modal(logoutModalEl);
    let logoutUrl = null;

    logoutLinks.forEach(link => {
      link.addEventListener('click', (event) => {
        event.preventDefault();
        logoutUrl = link.getAttribute('data-logout-url');
        logoutModal.show();
      });
    });

    logoutConfirmBtn.addEventListener('click', () => {
      if (logoutUrl) {
        logoutModal.hide();
        document.body.classList.add('sh-page-exit');
        setTimeout(() => {
          window.location.href = logoutUrl;
        }, 260);
      }
    });
  }

  // Auto-init Bootstrap tooltips if present
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
});

/**
 * Generic reusable delete-confirmation flow.
 * Usage: attach data-delete-url="ajax/employee.php?..." data-delete-name="Juan Dela Cruz"
 * to any button with class .sh-delete-btn, and pass a callback to run after success.
 */
function shConfirmDelete(name, onConfirm) {
  const modalEl = document.getElementById('shDeleteModal');
  if (!modalEl) { if (confirm(`Delete ${name}?`)) onConfirm(); return; }

  document.getElementById('shDeleteModalName').textContent = name;
  const modal = new bootstrap.Modal(modalEl);
  const confirmBtn = document.getElementById('shDeleteModalConfirm');

  const handler = async () => {
    confirmBtn.disabled = true;
    await onConfirm();
    confirmBtn.disabled = false;
    modal.hide();
  };
  confirmBtn.addEventListener('click', handler, { once: true });
  modal.show();
}
