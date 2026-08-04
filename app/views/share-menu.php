<?php
/**
 * Share control for one post — a pill that opens a two-option menu.
 * Expects:  $q  (a question row: id, title, body, post_number).
 *
 * The same string is escaped TWO different ways below and they are not
 * interchangeable:  e()  for the HTML attribute the copy handler reads,
 * rawurlencode()  for WhatsApp's query string. Swapping them is an XSS hole.
 *
 * Opening/closing needs no JavaScript of its own: data-toggle is already
 * implemented in app.js. Only the clipboard write is new behaviour.
 */
$shareId   = (int) $q['id'];
$shareText = share_text($q);
?>
<span class="share-wrap">
  <button class="pill" type="button" aria-haspopup="true" aria-controls="share-<?= $shareId ?>"
          data-toggle="share-<?= $shareId ?>" data-toggle-class="open">
    <svg class="ico ico-sm"><use href="#i-share"/></svg>Share
  </button>
  <div class="share-menu" id="share-<?= $shareId ?>">
    <button class="share-opt" type="button" data-share-copy="<?= e($shareText) ?>">
      <svg class="ico ico-sm"><use href="#i-link"/></svg><span class="share-lbl">Copy link</span>
    </button>
    <a class="share-opt" href="https://wa.me/?text=<?= rawurlencode($shareText) ?>"
       target="_blank" rel="noopener noreferrer">
      <svg class="ico ico-sm"><use href="#i-whatsapp"/></svg>WhatsApp
    </a>
  </div>
</span>
