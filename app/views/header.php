<?php
/** Top of every public page: <head>, the SVG icon sprite, and the top bar. */
$pageTitle = $pageTitle ?? SITE_NAME;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" href="/assets/img/logo.svg">
  <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>

<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <symbol id="i-heart" viewBox="0 0 24 24"><path d="M12 21S3.5 15.6 3.5 9.6C3.5 6.5 5.9 4.6 8.4 4.6c1.7 0 3 .9 3.6 2 .6-1.1 1.9-2 3.6-2 2.5 0 4.9 1.9 4.9 5 0 6-8.5 11.4-8.5 11.4z"/></symbol>
  <symbol id="i-comment" viewBox="0 0 24 24"><path d="M4 3h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H10l-4 4v-4H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/></symbol>
  <symbol id="i-share" viewBox="0 0 24 24"><path d="M14 3l7 7-7 7v-4.2c-5.2 0-8.4 1.6-10 5C4.3 12.6 7.6 8 14 7.2V3z"/></symbol>
  <symbol id="i-search" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M10 4a6 6 0 1 0 3.7 10.7l4.8 4.8 1.4-1.4-4.8-4.8A6 6 0 0 0 10 4zm-4 6a4 4 0 1 1 8 0 4 4 0 0 1-8 0z"/></symbol>
  <symbol id="i-home" viewBox="0 0 24 24"><path d="M12 3l9 8h-3v9h-5v-6h-2v6H6v-9H3z"/></symbol>
  <symbol id="i-clock" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zm0 2a7 7 0 1 1 0 14 7 7 0 0 1 0-14zm-1 2v6l5 2 .7-1.7L13 11.3V7z"/></symbol>
  <symbol id="i-info" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zm0 2a7 7 0 1 1 0 14 7 7 0 0 1 0-14zm-1 5h2v7h-2zm0-3h2v2h-2z"/></symbol>
  <symbol id="i-ask" viewBox="0 0 24 24"><path d="M4 3h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H10l-4 4v-4H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm7 3v2h2V6zm0 3v5h2V9z"/></symbol>
  <symbol id="i-mail" viewBox="0 0 24 24"><path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm1.4 2L12 12l7.6-5z"/></symbol>
</svg>

<header class="topbar<?= !($showSearch ?? true) ? ' topbar-admin' : '' ?>">
  <div class="topbar-inner">
    <a class="tb-left" href="<?= e(G123_HOME) ?>" target="_blank" rel="noopener noreferrer" aria-label="123Greetings">
      <?= g123_logo('lg') ?>
    </a>
    <?php if ($showSearch ?? true): ?>
      <form class="tb-center" method="get" action="/">
        <label class="searchwrap">
          <svg class="ico ico-sm"><use href="#i-search"/></svg>
          <input type="search" name="q" placeholder="Search questions" value="<?= e($_GET['q'] ?? '') ?>">
        </label>
      </form>
    <?php else: ?>
      <div class="tb-center admin-title"><?= e($pageTitle) ?></div>
    <?php endif; ?>
    <a class="tb-right" href="/">
      <span class="lockup">
        <img class="logo" src="/assets/img/logo.svg" alt="" width="34" height="34">
        <span class="lk-text"><b>Wake up</b> <i>with</i> <b class="ob">Bob</b></span>
      </span>
    </a>
  </div>
</header>
