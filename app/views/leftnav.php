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
