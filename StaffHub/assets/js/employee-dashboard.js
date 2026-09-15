/**
 * employee-dashboard.js
 * Drives employee/dashboard.php: a real-time ticking clock, and the
 * AJAX Clock In / Clock Out actions against ajax/attendance.php.
 */

document.addEventListener('DOMContentLoaded', () => {
  tickClock();
  setInterval(tickClock, 1000);
});

function tickClock() {
  const now = new Date();
  const clockEl = document.getElementById('liveClock');
  const dateEl = document.getElementById('liveDate');
  if (clockEl) clockEl.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  if (dateEl) dateEl.textContent = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
}

async function doClockAction(action) {
  const inBtn = document.getElementById('clockInBtn');
  const outBtn = document.getElementById('clockOutBtn');
  const activeBtn = action === 'clock_in' ? inBtn : outBtn;

  inBtn.disabled = true;
  outBtn.disabled = true;
  activeBtn.classList.add('is-loading');

  const data = await shPost('../ajax/attendance.php', { action });

  if (data) {
    shToast(data.message, data.success ? 'success' : 'danger');
    if (data.success) {
      setTimeout(() => window.location.reload(), 900);
      return;
    }
  }

  activeBtn.classList.remove('is-loading');

  // Restore button states on failure by re-checking today's status
  const status = await shAjax('../ajax/attendance.php?action=today_status');
  if (status && status.today) {
    inBtn.disabled = !!status.today.time_in;
    outBtn.disabled = !status.today.time_in || !!status.today.time_out;
  } else {
    inBtn.disabled = false;
    outBtn.disabled = true;
  }
}
