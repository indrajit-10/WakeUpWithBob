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
 *   data-sub-q="POST_ID" (subscribe button) → notes which post the popup was opened from
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

  // --- subscribe popup -----------------------------------------------------
  // Opening and closing is the plain data-toggle handler at the top of this file
  // (the button, the × and the dark backdrop all toggle "open"), so the popup
  // still works for anyone whose browser has an older copy of this file cached.
  // This adds what a dialog needs on top: Esc closes it, the page behind stops
  // scrolling, focus moves into it and back, and the post it was opened from is
  // noted for the admin list.
  var subOpener = null;
  var subOpenedAt = 0;

  function subscribePop() { return document.getElementById('subscribe-pop'); }

  // While the popup is open the rest of the page is held still: it cannot
  // scroll, and (inert) Tab cannot wander onto links hidden behind the backdrop.
  // The popup is a direct child of <body>, so its siblings are exactly "the rest
  // of the page".
  function lockPage(lock) {
    var root = document.documentElement;
    var pop = subscribePop();
    if (lock) {
      // the scrollbar disappears while locked — pad by its width so nothing jumps
      var bar = window.innerWidth - root.clientWidth;
      if (bar > 0) { document.body.style.paddingRight = bar + 'px'; }
      root.classList.add('subpop-lock');
    } else {
      root.classList.remove('subpop-lock');
      document.body.style.paddingRight = '';
    }
    var kids = document.body.children;
    for (var i = 0; i < kids.length; i++) {
      if (kids[i] !== pop) { kids[i].inert = lock; }
    }
  }

  function afterSubscribeToggle(pop, trigger) {
    if (pop.classList.contains('open')) {
      // Never lock the page behind a popup the reader cannot see (a browser
      // still holding an old styles.css has no rule to show it).
      if (window.getComputedStyle(pop).display === 'none') {
        pop.classList.remove('open');
        return;
      }
      lockPage(true);
      subOpenedAt = Date.now();
      subOpener = trigger;
      var q = pop.querySelector('input[name="q"]');
      if (q && trigger && trigger.hasAttribute('data-sub-q')) {
        q.value = trigger.getAttribute('data-sub-q');
      }
      var panel = pop.querySelector('.subpop-panel');
      if (panel) { panel.focus(); }        // the dialog, not the email box: no surprise phone keyboard
    } else {
      lockPage(false);
      if (subOpener && document.body.contains(subOpener)) { subOpener.focus(); }
      subOpener = null;
    }
  }

  // Runs after the data-toggle handler above (listeners fire in the order they
  // were added), so the popup has already been opened or closed by now.
  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-toggle="subscribe-pop"]');
    var pop = trigger ? subscribePop() : null;
    if (!pop) { return; }
    // A double-click on the button lands its second click on the backdrop that
    // has just appeared under the pointer. That must not shut it again at once.
    if (trigger.classList.contains('subpop-backdrop') && !pop.classList.contains('open')
        && Date.now() - subOpenedAt < 500) {
      pop.classList.add('open');
      return;
    }
    afterSubscribeToggle(pop, trigger.classList.contains('pill-sub') ? trigger : null);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') { return; }
    var pop = subscribePop();
    if (pop && pop.classList.contains('open')) {
      pop.classList.remove('open');
      afterSubscribeToggle(pop, null);
    }
  });

  // Subscribe forms: one click, one sign-up. The browser has already checked the
  // email box by the time this runs (submit only fires for a valid form).
  document.addEventListener('submit', function (event) {
    var form = event.target.closest('.subform');
    if (!form || event.defaultPrevented) { return; }
    var btn = form.querySelector('.subform-btn');
    if (btn) {
      btn.disabled = true;
      btn.setAttribute('data-label', btn.textContent);
      btn.textContent = 'Subscribing…';
    }
  });

  // Coming back with the Back button can restore the page exactly as it was left
  // (button still "Subscribing…", popup still open) — put both back to normal.
  window.addEventListener('pageshow', function () {
    var btns = document.querySelectorAll('.subform-btn[data-label]');
    for (var i = 0; i < btns.length; i++) {
      btns[i].disabled = false;
      btns[i].textContent = btns[i].getAttribute('data-label');
      btns[i].removeAttribute('data-label');
    }
    var pop = subscribePop();
    if (pop && pop.classList.contains('open')) {
      pop.classList.remove('open');
      lockPage(false);
    }
  });
})();
