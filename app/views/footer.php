<?php
/** Bottom of every page: a site-wide footer with navigation and legal links. */
$year = date('Y');
?>
<footer class="sitefoot">
  <div class="foot-inner">

    <div class="foot-col foot-brand">
      <span class="lockup">
        <img class="logo" src="/assets/img/logo.svg" alt="" width="30" height="30">
        <span class="lk-text"><b>Five Minutes</b> <i>with</i> <b class="ob">Bob</b></span>
      </span>
    </div>

    <nav class="foot-col">
      <div class="foot-h">Explore</div>
      <a href="/archive.php">Archive</a>
    </nav>

    <nav class="foot-col">
      <div class="foot-h">Help</div>
      <a href="#">FAQ</a>
      <a href="/contact.php">Contact us</a>
    </nav>

    <nav class="foot-col">
      <div class="foot-h">123 Greetings</div>
      <a href="<?= e(G123_ECARDS) ?>" target="_blank" rel="noopener noreferrer">eCards</a>
      <a href="<?= e(G123_BLOG) ?>" target="_blank" rel="noopener noreferrer">Message Board</a>
    </nav>

  </div>

  <div class="foot-bar">
    <span class="foot-copy">&copy; <?= e($year) ?> <?= e(SITE_NAME) ?></span>
    <span class="foot-legal">
      <a href="/privacy.php">Privacy</a>
      <span class="foot-dot">·</span>
      <a href="/terms.php">Terms</a>
    </span>
  </div>
</footer>
<?php if (!empty($showChrome)): /* public pages only — never on admin */ ?>
<?php /* The "Subscribe To Read More" popup. One per page, opened by the button on
         every post (data-toggle). The `hidden` attribute keeps it out of sight even
         for a browser still holding an older styles.css; the CSS overrides it when
         the popup is opened. app.js adds Esc-to-close and stops the page scrolling
         behind it. Posts to /subscribe.php, which only stores the email. */ ?>
<div id="subscribe-pop" class="subpop" hidden>
  <div class="subpop-backdrop" data-toggle="subscribe-pop" data-toggle-class="open" aria-hidden="true"></div>
  <div class="subpop-panel" role="dialog" aria-modal="true" aria-labelledby="subpop-title" tabindex="-1">
    <button class="subpop-close" type="button" data-toggle="subscribe-pop" data-toggle-class="open" aria-label="Close">&times;</button>
    <p class="subpop-kicker">Five Minutes with Bob · Every day</p>
    <h2 class="subpop-title" id="subpop-title">What does it mean <span class="nowrap">to be <em>human?</em></span></h2>
    <p class="subpop-intro">Every day, Bob sits down with a cup of coffee and asks one simple question. You are invited to sit with him.</p>
    <form class="subform" method="post" action="/subscribe.php">
      <?= csrf_field() ?>
      <input type="hidden" name="source" value="post">
      <input type="hidden" name="q" value="0">
      <div style="position:absolute; left:-9999px" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
      <input class="subform-input" type="email" name="email" required maxlength="254" pattern="[^@\s=+\-][^@\s]*@[A-Za-z0-9.\-]+\.[A-Za-z0-9\-]{2,}" title="Please enter a valid email address, like name@example.com" placeholder="your@email.com" autocomplete="email" aria-label="Your email address">
      <button class="subform-btn" type="submit">Subscribe free</button>
    </form>
    <p class="subpop-legal">By subscribing you agree to our <a href="/privacy.php">Privacy Policy</a>. Unsubscribe anytime — one click, no questions.</p>
    <p class="subpop-first">Your first newsletter arrives the next morning.</p>
    <p class="subpop-tip"><span aria-hidden="true">👈</span> If you don’t see it in your inbox, check Promotions or Spam — then move it to your inbox so you never miss one.</p>
  </div>
</div>
<?php endif; ?>
</body>
</html>
