<?php
/**
 * Shared left navigation — used by the desktop rail AND the mobile drawer.
 * Set $navActive to 'home' | 'archive' | 'engaging' | 'about' before including
 * to highlight the current item.
 */
$navActive = $navActive ?? '';
?>
<a class="nav<?= $navActive === 'home' ? ' active' : '' ?>" href="/"><svg class="ico"><use href="#i-home"/></svg>Home</a>
<a class="nav<?= $navActive === 'archive' ? ' active' : '' ?>" href="/archive.php"><svg class="ico"><use href="#i-clock"/></svg>Archive</a>
<a class="nav<?= $navActive === 'engaging' ? ' active' : '' ?>" href="/?sort=engaging"><svg class="ico"><use href="#i-comment"/></svg>Most Engaging</a>
<a class="nav<?= $navActive === 'about' ? ' active' : '' ?>" href="/about.php"><svg class="ico"><use href="#i-info"/></svg>About Bob</a>

<div class="widget cta-widget" style="margin-top: 20px;">
  <div class="widget-b" style="border-radius: 5px;padding: 10px 10px;gap: 10px;">
	  <a class="btn-orange btn-maroon" href="https://www.123greetings.com/birthday/happy_birthday/?utm_source=human_Birthday&amp;utm_medium=human" target="_blank" rel="noopener">Send Birthday eCards</a>
	  <a class="btn-orange btn-maroon" href="https://www.123greetings.com/events/thanksgiving/wishes//?utm_source=human_Thanksgiving&amp;utm_medium=human" target="_blank" rel="noopener">Send Thanksgiving eCards</a>
  </div>
</div>