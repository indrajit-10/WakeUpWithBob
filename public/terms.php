<?php
/**
 * Terms of Use  (  /terms.php  )  — friendly, plain-language house rules.
 */
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

ensure_session();

$updated = 'July 2026';   // ← update this date whenever you change the terms

$pageTitle = 'Terms · ' . SITE_NAME;
require __DIR__ . '/../app/views/header.php';
?>
<div class="app">
  <div class="shell">

    <nav class="left">
      <?php include __DIR__ . '/../app/views/leftnav.php'; ?>
    </nav>

    <main class="center">
      <div class="feedhead"><b>Terms of Use</b></div>

      <article class="post legal">
        <p class="legal-updated">Last updated: <?= e($updated) ?></p>

        <p class="post-text">These are the house rules for Wake up with Bob. They are meant to be readable, not
          scary. By using the site, you agree to them.</p>

        <h2>What this is</h2>
        <p class="post-text">Wake up with Bob is a free community where Bob posts one question each morning and
          readers reply and share. There are no accounts and nothing to buy.</p>

        <h2>Your posts</h2>
        <p class="post-text">You keep ownership of what you write. By posting, you give us permission to show it on
          the site once it has been approved. Please only post things you have the right to share.</p>

        <h2>Being a good neighbour</h2>
        <p class="post-text">Keep it kind. Do not post anything that is harassing, hateful, threatening, deceptive,
          illegal, spammy, or that impersonates someone else. Disagree gently, or simply scroll on by.</p>

        <h2>Moderation</h2>
        <p class="post-text">Every comment and reply is read by Bob before it appears. Bob may approve, decline,
          edit, or remove anything, at any time, and is under no obligation to publish a given post. This keeps the
          mornings a good place to be.</p>

        <h2>The site “as is”</h2>
        <p class="post-text">We work to keep the site running well, but it is provided “as is”, without warranties,
          and it may occasionally be unavailable or change. To the extent the law allows, we are not liable for any
          loss arising from your use of it.</p>

        <h2>123 Greetings</h2>
        <p class="post-text">Wake up with Bob is powered by 123 Greetings. Links to 123 Greetings websites in our
          footer lead to separate sites governed by their own terms.</p>

        <h2>Changes</h2>
        <p class="post-text">We may update these terms from time to time; when we do, we will change the date above.</p>

        <h2>Contact</h2>
        <p class="post-text">Questions about these terms? Email
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
