/* Applies the visitor's saved light/dark choice BEFORE first paint, so there's
 * no flash of the wrong theme. Loaded synchronously in <head>, ahead of the
 * stylesheet. If no choice is saved, the CSS falls back to the OS preference. */
(function () {
  try {
    var t = localStorage.getItem('theme');
    if (t === 'dark' || t === 'light') {
      document.documentElement.setAttribute('data-theme', t);
    }
  } catch (e) {}
})();
