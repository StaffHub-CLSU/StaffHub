/**
 * dashboard.js
 * Renders the three admin dashboard Chart.js widgets and polls
 * ajax/search.php?action=dashboard_stats every 30s to keep the stat
 * cards, recent-activity, and recent clock-in/out lists live without
 * a full page reload.
 */

const brandColors = {
  primary: '#0F766E',
  primaryAlt: '#14B8A6',
  primaryDark: '#115E59',
  secondary: '#8B95A7',
  success: '#059669',
  warning: '#D97706',
  info: '#0284C7',
  indigo: '#6366F1',
  purple: '#8B5CF6',
  muted: '#B6BFCB',
};

const doughnutPalette = ['#0F766E', '#14B8A6', '#059669', '#6366F1', '#0284C7', '#D97706'];

function shGradient(ctx, area, from, to, vertical = true) {
  const g = ctx.createLinearGradient(0, area.bottom, 0, area.top);
  g.addColorStop(0, from);
  g.addColorStop(1, to);
  return g;
}

/**
 * Chart.js draws its own canvas pixels, so it doesn't pick up our CSS
 * variables automatically. This reads the current theme's text/grid/card
 * colors and applies them to Chart.js globally, then nudges every chart on
 * the page to redraw with the new colors — kept in sync with the rest of
 * the UI whenever Dark Mode is toggled.
 */
const shDashboardCharts = [];

function shApplyChartTheme() {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const textColor = isDark ? '#A8B3C7' : '#5B6574';
  const gridColor = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(13,22,38,0.06)';
  const cardBg = isDark ? '#101A2E' : '#FFFFFF';

  Chart.defaults.color = textColor;
  Chart.defaults.borderColor = gridColor;
  Chart.defaults.font.family = "'Instrument Sans', -apple-system, 'Segoe UI', sans-serif";

  shDashboardCharts.forEach((chart) => {
    if (chart.options.scales?.x) chart.options.scales.x.grid = { color: gridColor };
    if (chart.options.scales?.y) chart.options.scales.y.grid = { color: gridColor };
    if (chart.config.type === 'doughnut') {
      chart.data.datasets.forEach((ds) => { ds.borderColor = cardBg; });
    }
    chart.update();
  });
}

window.addEventListener('sh:themechange', shApplyChartTheme);

document.addEventListener('DOMContentLoaded', () => {
  shApplyChartTheme();

  if (typeof dailyAttendanceData !== 'undefined') {
    const dailyAttendanceChart = new Chart(document.getElementById('chartDailyAttendance'), {
      type: 'line',
      data: {
        labels: dailyAttendanceData.labels,
        datasets: [{
          label: 'Employees Present',
          data: dailyAttendanceData.values,
borderColor: brandColors.primary,
            backgroundColor: (ctx) => {
              const { chart } = ctx;
              const { ctx: c, chartArea } = chart;
              if (!chartArea) return 'rgba(15,118,110,0.08)';
              return shGradient(c, chartArea, 'rgba(20,184,166,0.02)', 'rgba(15,118,110,0.24)');
            },
          fill: true,
          tension: 0.45,
          pointBackgroundColor: brandColors.primaryAlt,
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
          borderWidth: 2.5,
        }],
      },
      options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
      },
    });
    shDashboardCharts.push(dailyAttendanceChart);
  }

  if (typeof monthlyPayrollData !== 'undefined') {
    const monthlyPayrollChart = new Chart(document.getElementById('chartMonthlyPayroll'), {
      type: 'bar',
      data: {
        labels: monthlyPayrollData.labels,
        datasets: [{
          label: 'Net Payroll (₱)',
          data: monthlyPayrollData.values,
          backgroundColor: (ctx) => {
            const { chart } = ctx;
            const { ctx: c, chartArea } = chart;
            if (!chartArea) return brandColors.primary;
            return shGradient(c, chartArea, brandColors.primaryAlt, brandColors.primary);
          },
          borderRadius: 10,
          borderSkipped: false,
          maxBarThickness: 40,
        }],
      },
      options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } },
      },
    });
    shDashboardCharts.push(monthlyPayrollChart);
  }

  if (typeof deptDistributionData !== 'undefined') {
    const deptDistributionChart = new Chart(document.getElementById('chartDeptDistribution'), {
      type: 'doughnut',
      data: {
        labels: deptDistributionData.labels,
        datasets: [{
          data: deptDistributionData.values,
          backgroundColor: doughnutPalette,
          borderWidth: 3,
          borderColor: document.documentElement.getAttribute('data-theme') === 'dark' ? '#101A2E' : '#FFFFFF',
          hoverOffset: 6,
        }],
      },
      options: {
        cutout: '68%',
        plugins: {
          legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, pointStyle: 'circle', font: { size: 11 } } },
        },
      },
    });
    shDashboardCharts.push(deptDistributionChart);
  }

  // Periodic live refresh of stat cards + recent lists
  setInterval(refreshDashboardStats, 30000);
});

async function refreshDashboardStats() {
  const data = await shAjax(`${window.API_BASE}/search?action=dashboard_stats`);
  if (!data || !data.success) return;

  Object.entries(data.stats).forEach(([key, value]) => {
    const el = document.querySelector(`[data-stat="${key}"]`);
    if (el) el.textContent = value;
  });
}
