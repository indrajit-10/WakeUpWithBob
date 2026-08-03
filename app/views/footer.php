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
</body>
</html>
