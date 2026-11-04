<?php
/** Right rail: Ask Bob + Feedback. Forms post to /ask.php and /feedback.php.
 *  (The flash confirmation is rendered once as a top banner in header.php.) */
?>
<div class="widget d-none">
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

<div class="widget d-none">
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

<?php /* Sidebar buttons — maroon, above "Send Email To Bob". Text and links are
         edited in Admin → Settings (see app/ctas.php). Open in a new tab; plain
         "noopener" (no "noreferrer") so 123greetings.com can see the visit came from here. */
if (is_file(__DIR__ . '/../ctas.php')) { require_once __DIR__ . '/../ctas.php'; }   // never break the page if it is missing
if (function_exists('sidebar_ctas') && ($__ctas = sidebar_ctas())): ?>
<div class="widget cta-widget">
  <div class="widget-b">
    <?php foreach ($__ctas as $__cta): ?>
      <a class="btn-orange btn-maroon" href="<?= e($__cta['url']) ?>" target="_blank" rel="noopener"><?= sidebar_cta_icon_svg($__cta['icon']) ?><?= e($__cta['text']) ?></a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="widget">
  <img src="https://human.123greetings.com/assets/img/Bob-Profile.jpg" width="100%" height="auto" alt="Bob Profile Image">
  <div class="widget-b">
    <p>Would you like Bob to cover something or maybe share something with him.</p>
    <a href="https://human.123greetings.com/contact.php" class="btn-orange">Send Email To Bob</a>
  </div>
</div>

<?php /* Subscribe box — directly below "Send Email To Bob". This file is included
         twice per page (desktop right rail + mobile menu), so no ids in here.
         Same handler as the popup: /subscribe.php, which only stores the email. */ ?>
<div class="widget subbox">
  <div class="widget-b">
    <p class="subbox-text">Every day, Bob sits down with a cup of coffee and asks one simple question. You are invited to sit with him.</p>
    <form class="subform" method="post" action="/subscribe.php">
      <?= csrf_field() ?>
      <input type="hidden" name="source" value="sidebar">
      <div style="position:absolute; left:-9999px" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
      <input class="subform-input" type="email" name="email" required maxlength="254" pattern="[^@\s=+\-][^@\s]*@[A-Za-z0-9.\-]+\.[A-Za-z0-9\-]{2,}" title="Please enter a valid email address, like name@example.com" placeholder="your@email.com" autocomplete="email" aria-label="Your email address">
      <button class="subform-btn" type="submit">Subscribe free</button>
    </form>
  </div>
</div>
