/**
 * Admin composer toolbar — Bold / Italic / Underline + an emoji picker.
 *
 * The post box stays an ordinary <textarea>: the buttons wrap the selected text in
 * the same markers the server understands (**bold**, *italic*, ++underline++), so
 * what you write is what format_post_text() renders. Nothing here is trusted by the
 * server — it is a typing convenience only.
 *
 * Styles STACK: bolding italic text gives ***both***, and each button toggles only
 * its own style back off. The markers are re-emitted in a fixed order the renderer
 * always understands.
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
  // Peel order: ** before * so a bold edge is never misread as italic.
  var PEEL  = [['bold', '**'], ['underline', '++'], ['italic', '*']];
  var CANON = ['underline', 'bold', 'italic'];   // outer -> inner when re-emitting

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

  /** Toggle one style on a single line's text, stacking/unstacking cleanly.
   *  Leading/trailing whitespace is kept OUTSIDE the markers (the renderer needs
   *  the markers to hug the text), and any markers already wrapping the text are
   *  parsed so the requested style can be flipped without disturbing the others. */
  function toggleOnLine(line, styleName) {
    var lead = line.match(/^\s*/)[0];
    var tail = line.match(/\s*$/)[0];
    var core = line.slice(lead.length, line.length - tail.length);
    if (core === '') { return line; }            // blank / spaces only

    var styles = { bold: false, italic: false, underline: false };
    var changed = true;
    while (changed) {
      changed = false;
      for (var i = 0; i < PEEL.length; i++) {
        var name = PEEL[i][0], m = PEEL[i][1], L = m.length;
        if (core.length >= 2 * L && core.slice(0, L) === m && core.slice(-L) === m) {
          styles[name] = !styles[name];
          core = core.slice(L, core.length - L);
          changed = true;
          break;
        }
      }
    }

    styles[styleName] = !styles[styleName];       // flip the button that was clicked

    var open = '', close = '';
    for (var j = 0; j < CANON.length; j++) {
      var n = CANON[j];
      if (styles[n]) { open += MARKERS[n]; close = MARKERS[n] + close; }
    }
    return lead + open + core + close + tail;
  }

  /**
   * Toggle `mark`'s style on the current selection.
   *  • styles stack — Bold on italic text becomes ***both***;
   *  • each button removes only its own style;
   *  • spaces are kept outside the markers;
   *  • a multi-line selection is handled line by line (the renderer never matches
   *    across a newline).
   */
  function applyMark(field, mark) {
    var styleName = mark === '**' ? 'bold' : (mark === '++' ? 'underline' : 'italic');
    var value = field.value;
    var start = field.selectionStart;
    var end   = field.selectionEnd;

    // Nothing selected: drop in an empty pair and park the cursor in the middle.
    if (start === end) {
      replaceRange(field, start, end, mark + mark);
      field.setSelectionRange(start + mark.length, start + mark.length);
      return;
    }

    // Single-line selection: make the toggle FORGIVING of imprecise selections.
    // A drag across bold text often grabs one stray marker char but not the other
    // (e.g. "word*"), which used to defeat un-bolding and pile on more markers.
    if (value.slice(start, end).indexOf('\n') === -1) {
      // (1) Snap: trim any stray marker characters (* or +) off the selection edges,
      //     so "word*", "*word", "**word*" etc. all become just "word".
      var ts = start, te = end;
      while (ts < te && (value.charAt(ts) === '*' || value.charAt(ts) === '+')) { ts++; }
      while (te > ts && (value.charAt(te - 1) === '*' || value.charAt(te - 1) === '+')) { te--; }
      if (ts < te) { start = ts; end = te; }   // keep the snap only if text remains

      // (2) Expand outward to include whole markers hugging the selection, so the
      //     toggle sees the full token (e.g. the **…** around the word you grabbed).
      var grew = true;
      while (grew) {
        grew = false;
        for (var k = 0; k < PEEL.length; k++) {
          var mm = PEEL[k][1], LL = mm.length;
          if (value.slice(start - LL, start) === mm && value.slice(end, end + LL) === mm) {
            start -= LL; end += LL; grew = true; break;
          }
        }
      }
    }

    var selected = value.slice(start, end);
    var out = selected.split('\n').map(function (line) {
      return line.trim() === '' ? line : toggleOnLine(line, styleName);
    }).join('\n');

    replaceRange(field, start, end, out);
    field.setSelectionRange(start, start + out.length);
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

  /** Convert pasted HTML (Word / WordPress / etc.) into the app's plain-text
   *  markers, preserving only bold / italic / underline. The HTML is parsed in a
   *  DETACHED document — never inserted into the page, never executed — so this
   *  adds no XSS surface. Richer formatting (lists, headings, links, colours)
   *  simply falls through as plain text. */
  function htmlToMarkers(html) {
    var doc = new DOMParser().parseFromString(html, 'text/html');
    var SKIP  = /^(script|style|head|title|meta|link)$/;
    var BLOCK = /^(p|div|br|li|tr|h[1-6]|blockquote|section|article|ul|ol|table|pre)$/;
    var tokens = [];
    function styleOf(el) {
      var b = false, i = false, u = false, tag = el.tagName.toLowerCase();
      if (tag === 'b' || tag === 'strong') { b = true; }
      if (tag === 'i' || tag === 'em')     { i = true; }
      if (tag === 'u' || tag === 'ins')    { u = true; }
      var st = (el.getAttribute('style') || '').toLowerCase();
      var fw = st.match(/font-weight\s*:\s*([a-z0-9]+)/);
      if (fw) { var v = fw[1]; if (v === 'bold' || v === 'bolder' || parseInt(v, 10) >= 600) { b = true; } }
      if (/font-style\s*:\s*italic/.test(st)) { i = true; }
      if (/text-decoration[^;]*underline/.test(st)) { u = true; }
      return { b: b, i: i, u: u };
    }
    (function walk(node, cur) {
      for (var n = node.firstChild; n; n = n.nextSibling) {
        if (n.nodeType === 3) {
          var t = n.nodeValue.replace(/\s+/g, ' ');
          if (t) { tokens.push({ text: t, b: cur.b, i: cur.i, u: cur.u }); }
        } else if (n.nodeType === 1) {
          var tag = n.tagName.toLowerCase();
          if (SKIP.test(tag)) { continue; }
          if (tag === 'br') { tokens.push({ br: true }); continue; }
          var s = styleOf(n), isBlock = BLOCK.test(tag);
          if (isBlock) { tokens.push({ br: true }); }
          walk(n, { b: cur.b || s.b, i: cur.i || s.i, u: cur.u || s.u });
          if (isBlock) { tokens.push({ br: true }); }
        }
      }
    })(doc.body, { b: false, i: false, u: false });
    function wrapRun(text, st) {
      var lead = (text.match(/^\s*/) || [''])[0];
      var tail = (text.match(/\s*$/) || [''])[0];
      var core = text.slice(lead.length, text.length - tail.length);
      if (core === '') { return text; }
      var open = '', close = '';
      if (st.u) { open += MARKERS.underline; close = MARKERS.underline + close; }
      if (st.b) { open += MARKERS.bold;      close = MARKERS.bold + close; }
      if (st.i) { open += MARKERS.italic;    close = MARKERS.italic + close; }
      return lead + open + core + close + tail;
    }
    var parts = [], cur = null;
    function flush() { if (cur) { parts.push(wrapRun(cur.text, cur)); cur = null; } }
    tokens.forEach(function (tk) {
      if (tk.br) { flush(); parts.push('\n'); return; }
      if (cur && cur.b === tk.b && cur.i === tk.i && cur.u === tk.u) { cur.text += tk.text; }
      else { flush(); cur = { text: tk.text, b: tk.b, i: tk.i, u: tk.u }; }
    });
    flush();
    return parts.join('').replace(/[ \t]+\n/g, '\n').replace(/\n{3,}/g, '\n\n').replace(/ {2,}/g, ' ').trim();
  }

  // --- rich paste from Word / WordPress: keep bold / italic / underline ---------
  document.addEventListener('paste', function (event) {
    var field = event.target;
    if (!field || field.tagName !== 'TEXTAREA' || !field.hasAttribute('data-editor-field')) { return; }
    var cd = event.clipboardData || window.clipboardData;
    if (!cd) { return; }
    var html = cd.getData('text/html');
    if (!html) { return; }                         // plain-text paste — let the browser handle it
    var markers = htmlToMarkers(html);
    if (markers === '') { return; }
    event.preventDefault();
    var start = field.selectionStart, end = field.selectionEnd;
    replaceRange(field, start, end, markers);
    field.setSelectionRange(start + markers.length, start + markers.length);
  });

})();
