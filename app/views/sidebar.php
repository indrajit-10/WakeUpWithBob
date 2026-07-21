<?php
/** Right rail: Ask Bob + Feedback. Forms post to /ask.php and /feedback.php. */
$sidebarFlash = flash_get();
?>
<?php if ($sidebarFlash): ?>
  <div class="flash-note"><span class="dot"></span><?= e($sidebarFlash) ?></div>
<?php endif; ?>
<div class="widget">
  <div class="widget-h"><svg class="ico ico-sm"><use href="#i-ask"/></svg>Ask Bob a question</div>
  <div class="widget-b">
    <p>Got something you'd love Bob to put to the morning crowd? Send it over — he reads every one.</p>
    <form method="post" action="/ask.php">
      <?= csrf_field() ?>
      <textarea class="w-textarea" name="body" placeholder="What should Bob ask tomorrow?"></textarea>
      <input class="w-input" type="email" name="email" placeholder="Your email (optional)">
      <button class="btn-orange" type="submit">Send to Bob</button>
    </form>
    <div class="tiny"><span class="dot"></span>Goes to Bob's desk.</div>
  </div>
</div>

<div class="widget">
  <div class="widget-h"><svg class="ico ico-sm"><use href="#i-mail"/></svg>Feedback</div>
  <div class="widget-b">
    <p>Spotted a bug, or have an idea to make the mornings better?</p>
    <form method="post" action="/feedback.php">
      <?= csrf_field() ?>
      <textarea class="w-textarea" name="body" placeholder="Your feedback…"></textarea>
      <button class="btn-orange" type="submit">Send feedback</button>
    </form>
  </div>
</div>
