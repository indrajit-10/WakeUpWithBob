<?php
/**
 * Mobile-only slide-in menu, opened by the header hamburger (data-toggle).
 * Holds the left-nav links plus the Ask Bob / Feedback widgets, so everything
 * that lives in the side rails on desktop is reachable on a phone.
 * Hidden entirely on desktop (see .mdrawer in styles.css).
 */
$navActive = $navActive ?? '';
?>
<div id="mobile-drawer" class="mdrawer">
  <div class="mdrawer-backdrop" data-toggle="mobile-drawer" data-toggle-class="open" aria-hidden="true"></div>
  <div class="mdrawer-panel" role="dialog" aria-label="Menu">
    <button class="mdrawer-close" type="button" data-toggle="mobile-drawer" data-toggle-class="open" aria-label="Close menu">&times;</button>
    <nav class="mdrawer-nav">
      <?php include __DIR__ . '/leftnav.php'; ?>
    </nav>
    <div class="mdrawer-widgets">
      <?php include __DIR__ . '/sidebar.php'; ?>
    </div>
  </div>
</div>
