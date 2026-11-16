/* Applies the visitor's saved light/dark choice BEFORE first paint, so there's
 * no flash of the wrong theme. Loaded synchronously in <head>, ahead of the
 * stylesheet. If no choice is saved, the CSS falls back to the OS preference.
 *
 * FORCE_THEME: the site is currently shown in LIGHT mode only, whatever the
 * device is set to or a visitor saved earlier. Set it to '' to bring the saved
 * choice and the OS preference back (and see the note at the top of
 * app/views/header.php). */
(function () {
  var FORCE_THEME = 'light';
  try {
    var t = FORCE_THEME || localStorage.getItem('theme');
    if (t === 'dark' || t === 'light') {
      document.documentElement.setAttribute('data-theme', t);
    }
  } catch (e) {}
})();
