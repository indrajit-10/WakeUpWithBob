/**
 * Admin composer toolbar — Bold / Italic / Underline + an emoji picker.
 *
 * The post box stays an ordinary <textarea>: the buttons wrap the selected text in
 * the same markers the server understands (**bold**, *italic*, ++underline++), so
 * what you write is what format_post_text() renders. Nothing here is trusted by the
 * server — it is a typing convenience only.
 *
 * No inline JS anywhere (the site's CSP is script-src 'self'); everything is wired
 * by event delegation on data-* attributes, matching app.js.
 *
 *   data-editor-cmd="bold|italic|underline"   toolbar button, wraps the selection
 *   data-editor-emoji="😀"                    inserts that character at the cursor
 *   data-editor-target="ID"                   set on the toolbar: which textarea it drives
 */
(function () {
  'use strict';

  var MARKERS = { bold: '**', italic: '*', underline: '++' };

  /** The textarea a toolbar control belongs to. */
  function targetOf(el) {
    var bar = el.closest('[data-editor-target]');
    return bar ? document.getElementById(bar.getAttribute('data-editor-target')) : null;
  }

  /** Put `value` into the field, keeping undo history working where supported. */
  function replaceRange(field, start, end, value) {
    field.focus();
    field.setSelectionRange(start, end);
    // execCommand keeps the browser's native undo stack intact; setRangeText is the
    // modern fallback (undo is coarser there, but nothing breaks).
    var ok = false;
    try { ok = document.execCommand('insertText', false, value); } catch (e) { ok = false; }
    if (!ok) {
      if (typeof field.setRangeText === 'function') {
        field.setRangeText(value, start, end, 'end');
      } else {
        field.value = field.value.slice(0, start) + value + field.value.slice(end);
      }
      field.dispatchEvent(new Event('input', { bubbles: true }));
    }
  }

  /**
   * Wrap (or unwrap) the current selection in `mark`.
   *
   * Details that matter for the server-side renderer:
   *  • markers must hug the text — "** bold **" does NOT render, so leading/trailing
   *    whitespace inside the selection is pushed back outside the markers;
   *  • the renderer never matches across a newline, so a multi-line selection is
   *    wrapped line by line instead of once around the whole block;
   *  • an already-wrapped selection toggles back off.
   */
  function applyMark(field, mark) {
    var value = field.value;
    var start = field.selectionStart;
    var end = field.selectionEnd;

    // Nothing selected: drop in an empty pair and park the cursor in the middle.
    if (start === end) {
      replaceRange(field, start, end, mark + mark);
      field.setSelectionRange(start + mark.length, start + mark.length);
      return;
    }

    var selected = value.slice(start, end);

    // Already wrapped? Unwrap — either inside the selection or just outside it.
    var inner = selected.slice(mark.length, selected.length - mark.length);
    if (selected.length > mark.length * 2 &&
        selected.slice(0, mark.length) === mark &&
        selected.slice(-mark.length) === mark) {
      replaceRange(field, start, end, inner);
      field.setSelectionRange(start, start + inner.length);
      return;
    }
    if (value.slice(Math.max(0, start - mark.length), start) === mark &&
        value.slice(end, end + mark.length) === mark) {
      replaceRange(field, start - mark.length, end + mark.length, selected);
      field.setSelectionRange(start - mark.length, start - mark.length + selected.length);
      return;
    }

    // Wrap each non-blank line separately so multi-line selections still render.
    var wrapped = selected.split('\n').map(function (line) {
      if (line.trim() === '') { return line; }              // keep blank lines blank
      var lead = line.match(/^\s*/)[0];
      var tail = line.match(/\s*$/)[0];
      var core = line.slice(lead.length, line.length - tail.length);
      return lead + mark + core + mark + tail;
    }).join('\n');

    replaceRange(field, start, end, wrapped);
    field.setSelectionRange(start, start + wrapped.length);
  }

  /** Insert a plain character (emoji) at the cursor. */
  function insertText(field, text) {
    var start = field.selectionStart;
    var end = field.selectionEnd;
    replaceRange(field, start, end, text);
    var at = start + text.length;
    field.setSelectionRange(at, at);
  }

  // --- toolbar + emoji clicks ------------------------------------------------
  document.addEventListener('click', function (event) {
    var cmd = event.target.closest('[data-editor-cmd]');
    if (cmd) {
      event.preventDefault();
      var field = targetOf(cmd);
      var mark = MARKERS[cmd.getAttribute('data-editor-cmd')];
      if (field && mark) { applyMark(field, mark); }
      return;
    }

    var emoji = event.target.closest('[data-editor-emoji]');
    if (emoji) {
      event.preventDefault();
      var f = targetOf(emoji);
      if (f) { insertText(f, emoji.getAttribute('data-editor-emoji')); }
      return;
    }

    // Clicking outside an open emoji panel closes it.
    if (!event.target.closest('.emoji-wrap')) {
      Array.prototype.forEach.call(document.querySelectorAll('.emoji-panel.visible'), function (p) {
        p.classList.remove('visible');
      });
    }
  });

  // --- Ctrl/Cmd + B / I / U inside a composer textarea ----------------------
  document.addEventListener('keydown', function (event) {
    if (!(event.ctrlKey || event.metaKey) || event.altKey) { return; }
    var field = event.target;
    if (!field || field.tagName !== 'TEXTAREA' || !field.hasAttribute('data-editor-field')) { return; }
    var key = event.key.toLowerCase();
    var mark = key === 'b' ? MARKERS.bold : key === 'i' ? MARKERS.italic : key === 'u' ? MARKERS.underline : null;
    if (!mark) { return; }
    event.preventDefault();
    applyMark(field, mark);
  });

  // --- emoji search filter --------------------------------------------------
  document.addEventListener('input', function (event) {
    var box = event.target;
    if (!box.hasAttribute || !box.hasAttribute('data-emoji-search')) { return; }
    var panel = box.closest('.emoji-panel');
    if (!panel) { return; }
    var q = box.value.trim().toLowerCase();
    Array.prototype.forEach.call(panel.querySelectorAll('[data-editor-emoji]'), function (btn) {
      var name = (btn.getAttribute('aria-label') || '').toLowerCase();
      btn.hidden = q !== '' && name.indexOf(q) === -1;
    });
    // hide a category heading whose emoji are all filtered out
    Array.prototype.forEach.call(panel.querySelectorAll('.emoji-group'), function (group) {
      var any = group.querySelector('[data-editor-emoji]:not([hidden])');
      group.hidden = !any;
    });
  });
})();
