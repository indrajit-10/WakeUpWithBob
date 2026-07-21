<?php
/**
 * Privacy Policy  (  /privacy.php  )  — plain-language, and honest to what the
 * app actually stores (comments, an optional email, and two cookies).
 */
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

ensure_session();

$updated = 'July 2026';   // ← update this date whenever you change the policy

$pageTitle = 'Privacy · ' . SITE_NAME;
require __DIR__ . '/../app/views/header.php';
?>
<div class="app">
  <div class="shell">

    <nav class="left">
      <a class="nav" href="/"><svg class="ico"><use href="#i-home"/></svg>Home</a>
      <a class="nav" href="/archive.php"><svg class="ico"><use href="#i-clock"/></svg>Archive</a>
      <div class="rail-sep"></div>
      <div class="rail-label">Community</div>
      <a class="nav" href="/about.php"><svg class="ico"><use href="#i-info"/></svg>About Bob</a>
      <a class="nav" href="/faq.php"><svg class="ico"><use href="#i-comment"/></svg>FAQ</a>
      <a class="nav" href="/contact.php"><svg class="ico"><use href="#i-mail"/></svg>Contact</a>
    </nav>

    <main class="center">
      <div class="feedhead"><b>Privacy Policy</b></div>

      <article class="post legal">
        <p class="legal-updated">Last updated: <?= e($updated) ?></p>

        <p class="post-text">Wake up with Bob is meant to be a calm, simple place, and we keep the data
          side just as simple. This page explains exactly what we collect, why, and what we do not do.</p>

        <h2>What we collect</h2>
        <ul>
          <li><b>What you post.</b> The comments and replies you submit, and the name you optionally attach to them.</li>
          <li><b>An email address — only if you give one.</b> When you use “Ask Bob a question”, “Send feedback”, or the Contact form, you may add an email so Bob can reply. It is never required, and never shown publicly.</li>
          <li><b>Two small cookies.</b> A session cookie that keeps the site working (and remembers the name you last commented with, on this device), and a <i>like</i> cookie that records which posts you have liked so you are not counted twice. That like cookie is the only “identity” behind a like.</li>
        </ul>

        <h2>What we do with it</h2>
        <ul>
          <li>Show your comment or reply once Bob has approved it.</li>
          <li>Remember your likes so tapping the heart again removes them.</li>
          <li>Let Bob read and, if you left an email, reply to a question or note you sent in.</li>
        </ul>

        <h2>What we don’t do</h2>
        <ul>
          <li>We do not sell or rent your information.</li>
          <li>We do not run third-party advertising or tracking cookies.</li>
          <li>We do not ask for accounts, passwords, or anything we don’t need.</li>
        </ul>

        <h2>Moderation</h2>
        <p class="post-text">Every comment and reply is read by Bob before it appears in public. Until then, a
          faded copy of your own comment is visible only to you, on your own device.</p>

        <h2>Keeping and removing your data</h2>
        <p class="post-text">Comments and messages are kept for as long as the site runs. If you would like something
          you posted removed, just ask via the <a href="/contact.php">Contact page</a> and we will take care of it.</p>

        <h2>123 Greetings</h2>
        <p class="post-text">Wake up with Bob is powered by 123 Greetings. If you follow a link from our footer to a
          123 Greetings website, your visit there is covered by that site’s own privacy policy, not this one.</p>

        <h2>Changes</h2>
        <p class="post-text">If we change how we handle your information, we will update this page and the date above.</p>

        <h2>Contact</h2>
        <p class="post-text">Questions about privacy? Email
          <a href="mailto:<?= e(CONTACT_EMAIL) ?>"><?= e(CONTACT_EMAIL) ?></a> or use the
          <a href="/contact.php">Contact page</a>.</p>
      </article>
    </main>

    <aside class="right">
      <?php include __DIR__ . '/../app/views/sidebar.php'; ?>
    </aside>

  </div>
</div>
<?php require __DIR__ . '/../app/views/footer.php'; ?>
