/* Wake up with Bob — small progressive-enhancement helpers.
 *
 * Kept in an external file (no inline on* handlers anywhere in the markup) so the
 * site can ship a strict  script-src 'self'  Content-Security-Policy. Behaviour is
 * driven by data-attributes and event delegation, so it also works for elements
 * added to the page later.
 *
 *   data-toggle="ELEMENT_ID"                → click toggles a class on #ELEMENT_ID
 *   data-toggle-class="visible"             → which class to toggle (default: "visible")
 *   data-scrollto="ELEMENT_ID"              → click smooth-scrolls #ELEMENT_ID into view
 *   data-dismiss-closest=".selector"        → click removes the nearest matching ancestor
 *   data-theme-toggle                       → click flips light/dark and remembers it
 *   data-navigate (on a <select>)           → changing it navigates to the chosen option's value
 *   data-confirm="Are you sure?"            → submit is cancelled unless the user confirms
 *   data-share-copy="TEXT"                  → click copies TEXT to the clipboard
 */
(function () {
  'use strict';

  document.addEventListener('click', function (event) {
    var toggle = event.target.closest('[data-toggle]');
    if (toggle) {
      var target = document.getElementById(toggle.getAttribute('data-toggle'));
      if (target) {
        target.classList.toggle(toggle.getAttribute('data-toggle-class') || 'visible');
      }
    }

    var scroller = event.target.closest('[data-scrollto]');
    if (scroller) {
      var dest = document.getElementById(scroller.getAttribute('data-scrollto'));
      if (dest) {
        dest.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }

    var dismiss = event.target.closest('[data-dismiss-closest]');
    if (dismiss) {
      var box = dismiss.closest(dismiss.getAttribute('data-dismiss-closest'));
      if (box) {
        box.remove();
      }
    }

    if (event.target.closest('[data-theme-toggle]')) {
      var root = document.documentElement;
      var current = root.getAttribute('data-theme');
      if (current !== 'dark' && current !== 'light') {
        // no explicit choice yet — start from whatever the OS is showing
        current = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      }
      var next = current === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try { localStorage.setItem('theme', next); } catch (e) {}
    }
  });

  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-confirm]');
    if (form && !window.confirm(form.getAttribute('data-confirm'))) {
      event.preventDefault();
    }
  });

  document.addEventListener('change', function (event) {
    var nav = event.target.closest('[data-navigate]');
    if (nav && nav.value) {
      window.location.href = nav.value;
    }
  });

  // --- share menus -------------------------------------------------------
  // The panel itself is opened by the data-toggle handler above; this only
  // keeps one open at a time and closes it the ways people expect.
  function closeShareMenus(except) {
    var open = document.querySelectorAll('.share-menu.open');
    for (var i = 0; i < open.length; i++) {
      if (open[i] !== except) { open[i].classList.remove('open'); }
    }
  }

  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-toggle]');
    var panel   = trigger ? document.getElementById(trigger.getAttribute('data-toggle')) : null;
    if (panel && panel.classList.contains('share-menu')) {
      closeShareMenus(panel);                       // opening one closes the others
      return;
    }
    if (!event.target.closest('.share-menu')) {
      closeShareMenus(null);                        // a click anywhere else closes them all
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') { closeShareMenus(null); }
  });

  // Copy the share text. iOS Safari only honours the write when it happens in the
  // SAME TICK as the click — so the text is read straight off the attribute. No
  // await, no fetch, nothing between the gesture and writeText().
  document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-share-copy]');
    if (!btn) { return; }
    var text  = btn.getAttribute('data-share-copy');
    var label = btn.querySelector('.share-lbl');

    var done = function (ok) {
      if (!label) { return; }
      var original = label.getAttribute('data-original') || label.textContent;
      label.setAttribute('data-original', original);
      label.textContent = ok ? 'Copied!' : 'Couldn’t copy';
      window.setTimeout(function () {
        label.textContent = original;
        closeShareMenus(null);
      }, 1200);
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () { done(true); }, function () { done(false); });
      return;
    }
    // Older browsers, or any non-HTTPS origin where navigator.clipboard is undefined.
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.position = 'fixed';
    ta.style.opacity  = '0';
    document.body.appendChild(ta);
    ta.select();
    var ok = false;
    try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
    document.body.removeChild(ta);
    done(ok);
  });
})();
