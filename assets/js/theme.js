/**
 * theme.js
 * Light / Dark mode for the whole app.
 *
 * How it works (kept simple on purpose):
 * - The theme name ("light" or "dark") is saved in localStorage under 'sh-theme'.
 * - We put that name on <html data-theme="..."> because every CSS color in
 *   style.css is written as a variable that changes based on that attribute.
 * - A tiny inline script in <head> (see header.php / index.php) sets the
 *   attribute BEFORE the page paints, so there is no flash of the wrong theme.
 * - This file wires up the toggle buttons and keeps every toggle on the page
 *   in sync, and tells the rest of the app (e.g. charts) when the theme changes.
 */

const SH_THEME_KEY = 'sh-theme';

/** Reads the saved theme, falling back to the visitor's OS preference. */
function shGetPreferredTheme() {
  const saved = localStorage.getItem(SH_THEME_KEY);
  if (saved === 'light' || saved === 'dark') return saved;
  return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

/** Applies a theme to the page and remembers it for next time. */
function shSetTheme(theme, { save = true } = {}) {
  document.documentElement.setAttribute('data-theme', theme);
  document.documentElement.setAttribute('data-bs-theme', theme); // rides on Bootstrap 5.3's own dark mode
  if (save) localStorage.setItem(SH_THEME_KEY, theme);

  // Keep every toggle control on the page (topbar icon + profile menu item) in sync.
  document.querySelectorAll('[data-theme-toggle]').forEach((el) => {
    const isDark = theme === 'dark';
    el.setAttribute('aria-pressed', String(isDark));
    const icon = el.querySelector('[data-theme-icon]');
    if (icon) icon.className = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    const label = el.querySelector('[data-theme-label]');
    if (label) label.textContent = isDark ? 'Light Mode' : 'Dark Mode';
    if (el.matches('input[type="checkbox"]')) el.checked = isDark;
  });

  // Let anything else on the page (e.g. Chart.js instances) react.
  window.dispatchEvent(new CustomEvent('sh:themechange', { detail: { theme } }));
}

function shToggleTheme() {
  const current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
  shSetTheme(current === 'dark' ? 'light' : 'dark');
}

document.addEventListener('DOMContentLoaded', () => {
  // Make sure the attribute matches storage (the early inline script already
  // did this before paint, but a few pages don't include it, so this is the
  // safety net) and sync the toggle controls to match.
  shSetTheme(shGetPreferredTheme(), { save: false });

  document.querySelectorAll('[data-theme-toggle]').forEach((el) => {
    el.addEventListener('click', (e) => {
      e.preventDefault();
      shToggleTheme();
    });
  });

  // Follow the OS theme automatically only if the visitor never picked one themselves.
  if (window.matchMedia && !localStorage.getItem(SH_THEME_KEY)) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
      if (!localStorage.getItem(SH_THEME_KEY)) shSetTheme(e.matches ? 'dark' : 'light', { save: false });
    });
  }
});
