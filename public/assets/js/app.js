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
 *   data-confirm="Are you sure?"            → submit is cancelled unless the user confirms
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
  });

  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-confirm]');
    if (form && !window.confirm(form.getAttribute('data-confirm'))) {
      event.preventDefault();
    }
  });
})();
