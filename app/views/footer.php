<?php
/** Bottom of every page: a site-wide footer with navigation, legal, and the 123 Greetings family. */
$year = date('Y');
?>
<footer class="sitefoot">
  <div class="foot-inner">

    <div class="foot-col foot-brand">
      <span class="lockup">
        <img class="logo" src="/assets/img/logo.svg" alt="" width="30" height="30">
        <span class="lk-text"><b>Wake up</b> <i>with</i> <b class="ob">Bob</b></span>
      </span>
      <p class="foot-tag">One question every morning, and a whole town that answers.</p>
      <div class="foot-powered">Powered by <?= g123_logo() ?></div>
    </div>

    <nav class="foot-col">
      <div class="foot-h">Explore</div>
      <a href="/">Home</a>
      <a href="/archive.php">Archive</a>
      <a href="/about.php">About Bob</a>
    </nav>

    <nav class="foot-col">
      <div class="foot-h">Help</div>
      <a href="/faq.php">FAQ</a>
      <a href="/contact.php">Contact</a>
    </nav>

    <nav class="foot-col">
      <div class="foot-h">123 Greetings</div>
      <a href="<?= e(G123_HOME) ?>" target="_blank" rel="noopener noreferrer">123Greetings.com</a>
      <a href="<?= e(G123_ECARDS) ?>" target="_blank" rel="noopener noreferrer">Free eCards</a>
      <a href="<?= e(G123_BLOG) ?>" target="_blank" rel="noopener noreferrer">Blog</a>
    </nav>

  </div>

  <div class="foot-bar">
    <span class="foot-copy">&copy; <?= e($year) ?> <?= e(SITE_NAME) ?> · Powered by <?= g123_logo() ?></span>
    <span class="foot-legal">
      <a href="/privacy.php">Privacy</a>
      <span class="foot-dot">·</span>
      <a href="/terms.php">Terms</a>
    </span>
  </div>
</footer>
</body>
</html>
