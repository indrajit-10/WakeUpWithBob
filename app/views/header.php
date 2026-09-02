<?php
/** Top of every public page: <head>, the SVG icon sprite, and the top bar. */
ensure_session();                         // start the session before any output (cookie-safe)
$pageTitle = $pageTitle ?? SITE_NAME;
$showChrome = $showSearch ?? true;        // public pages get search + nav; admin pages don't
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" href="/assets/img/logo.svg">
  <script src="/assets/js/theme-init.js"></script>
  <link rel="stylesheet" href="/assets/css/styles.css">
  <script src="/assets/js/app.js" defer></script>
  <?php /* Page-specific scripts (e.g. the admin editor). Local paths only — the
           CSP allows script-src 'self', and nothing here is ever user-supplied. */ ?>
  <?php foreach (($extraJs ?? []) as $__js): ?>
    <script src="<?= e($__js) ?>" defer></script>
  <?php endforeach; ?>
  <!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-50SCE7ZLTC"></script>
	<script nonce="<?= e(csp_nonce()) ?>">
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag('js', new Date());

	  gtag('config', 'G-50SCE7ZLTC');
	</script>
	<meta name="google-site-verification" content="wsf3_b5jSHSkJDsqCbtEyY_YWnQ3ze2lxpyfpP5fmyA" />
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
  <symbol id="i-menu" viewBox="0 0 24 24"><path d="M3 6h18v2H3zM3 11h18v2H3zM3 16h18v2H3z"/></symbol>
  <symbol id="i-sun" viewBox="0 0 24 24"><path d="M12 17a5 5 0 110-10 5 5 0 010 10zm0-13a1 1 0 011 1v1a1 1 0 11-2 0V5a1 1 0 011-1zm0 14a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM4 12a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm14 0a1 1 0 011-1h1a1 1 0 110 2h-1a1 1 0 01-1-1zM6.3 6.3a1 1 0 011.4 0l.7.7A1 1 0 116.99 8.4l-.7-.7a1 1 0 010-1.4zm9.9 9.9a1 1 0 011.4 0l.7.7a1 1 0 11-1.4 1.4l-.7-.7a1 1 0 010-1.4zM17.7 6.3a1 1 0 010 1.4l-.7.7A1 1 0 1115.6 6.99l.7-.7a1 1 0 011.4 0zM8.4 15.6a1 1 0 010 1.4l-.7.7A1 1 0 015.7 16.9l.7-.7a1 1 0 011.4 0z"/></symbol>
  <symbol id="i-moon" viewBox="0 0 24 24"><path d="M21 12.8A8.5 8.5 0 1111.2 3a1 1 0 01.3 1.98 6.5 6.5 0 108.6 8.6 1 1 0 011.98.22z"/></symbol>
  <symbol id="i-link" viewBox="0 0 24 24"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></symbol>
  <symbol id="i-whatsapp" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></symbol>
</svg>

<header class="topbar<?= !$showChrome ? ' topbar-admin' : '' ?>">
  <div class="topbar-inner">
    <div class="tb-left">
      <?php if ($showChrome): ?>
        <button class="hamburger" type="button" data-toggle="mobile-drawer" data-toggle-class="open" aria-label="Open menu" aria-controls="mobile-drawer">
          <svg class="ico"><use href="#i-menu"/></svg>
        </button>
      <?php endif; ?>
      <a class="tb-brand" href="<?= e(G123_HOME) ?>" target="_blank" rel="noopener noreferrer" aria-label="123Greetings">
        <?= g123_logo('lg') ?>
      </a>
    </div>

    <?php if ($showChrome): ?>
      <form class="tb-center" method="get" action="/">
        <label class="searchwrap">
          <svg class="ico ico-sm"><use href="#i-search"/></svg>
          <input type="search" name="q" placeholder="Search questions" value="<?= e($_GET['q'] ?? '') ?>">
        </label>
      </form>
    <?php else: ?>
      <div class="tb-center admin-title"><?= e($pageTitle) ?></div>
    <?php endif; ?>

    <div class="tb-right d-none">
      <button class="theme-toggle" type="button" data-theme-toggle aria-label="Toggle light or dark theme" title="Toggle light / dark">
        <svg class="ico ico-sun"><use href="#i-sun"/></svg>
        <svg class="ico ico-moon"><use href="#i-moon"/></svg>
      </button>
      <!--<a class="tb-lockup" href="/">-->
        <span class="lockup">
          <img class="logo" src="/assets/img/logo.svg" alt="" width="34" height="34">
          <span class="lk-text"><b>Five Minutes</b> <i>with</i> <b class="ob">Bob</b></span>
        </span>
      <!--</a>-->
    </div>
  </div>
</header>
<?php if ($showChrome && ($__flash = flash_get())): ?>
  <div class="flash-bar"><div class="flash-note"><span class="dot"></span><?= e($__flash) ?></div></div>
<?php endif; ?>
<?php if ($showChrome) { $navActive = $navActive ?? ''; include __DIR__ . '/mobilemenu.php'; } ?>
