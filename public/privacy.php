<?php
/**
 * Privacy Policy  (  /privacy.php  )  — plain-language, and honest to what the
 * app actually stores: comments, an optional email, a session cookie, anonymous
 * search logs, and Google Analytics (see app/views/header.php).
 *
 * IF YOU REMOVE THE GOOGLE ANALYTICS TAG, delete the "Visitor statistics"
 * section and the analytics cookie bullet below — this page must always match
 * what the site actually does.
 */
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

ensure_session();

$updated = 'August 2026';   // ← update this date whenever you change the policy

$pageTitle = 'Privacy · ' . SITE_NAME;
require __DIR__ . '/../app/views/header.php';
?>
<div class="app">
  <div class="shell">

    <nav class="left">
      <?php include __DIR__ . '/../app/views/leftnav.php'; ?>
    </nav>

    <main class="center">
      <div class="feedhead"><b>Privacy Policy</b></div>

      <article class="post legal">
        <p class="legal-updated">Last updated: <?= e($updated) ?></p>

        <p class="post-text"><?= e(SITE_NAME) ?> is meant to be a calm, simple place, and we keep the data
          side just as simple. This page explains exactly what we collect, why, and what we do not do.</p>

        <h2>What we collect</h2>
        <ul>
          <li><b>What you post.</b> The comments and replies you submit, and the name you optionally attach to them.</li>
          <li><b>An email address — only if you give one.</b> When you use “Ask Bob a question”, “Send feedback”, or the Contact form, you may add an email so Bob can reply. It is never required, and never shown publicly.</li>
          <li><b>A session cookie of our own.</b> It keeps the site working, and remembers the name you last commented with, on this device.</li>
          <li><b>Google Analytics cookies.</b> We use Google Analytics to count visits and see which mornings people read. It sets its own cookies and sends some information to Google — see <a href="#analytics">Visitor statistics</a> below.</li>
          <li><b>Anonymous search logs.</b> The words you type into search and how many results came back, with a timestamp. This is <i>not</i> linked to you — no IP address, no name, no cookie tie-in.</li>
        </ul>

        <h2>What we do with it</h2>
        <ul>
          <li>Show your comment or reply once Bob has approved it.</li>
          <li>Let Bob read and, if you left an email, reply to a question or note you sent in.</li>
          <li>Keep an anonymous tally of what people search for, so Bob can see what readers are looking for and write better mornings.</li>
        </ul>

        <h2 id="analytics">Visitor statistics</h2>
        <p class="post-text">We use <b>Google Analytics</b> to understand how the site is doing — how many
          people visit, which mornings get read, and roughly where readers come from. To do that, Google
          sets cookies in your browser and receives information about your visit, including the pages you
          view, your approximate location, your device and browser, and the site you arrived from. Google
          processes this on our behalf and may store it on servers outside your country.</p>
        <p class="post-text">We only ever look at this in aggregate — we are not trying to identify you, and
          we do not combine it with your comments or your email. We do not use it for advertising, and we do
          not allow Google to use it to target ads to you.</p>
        <p class="post-text"><b>If you would rather not be counted:</b> you can install Google’s official
          <a href="https://tools.google.com/dlpage/gaoptout" rel="noopener noreferrer" target="_blank">opt-out
          browser add-on</a>, block analytics cookies in your browser settings, or use your browser’s private
          window. The site works exactly the same either way. Google explains what it does with this data in
          its <a href="https://policies.google.com/privacy" rel="noopener noreferrer" target="_blank">privacy
          policy</a>.</p>

        <h2>What we don’t do</h2>
        <ul>
          <li>We do not sell or rent your information.</li>
          <li>We do not run advertising, and nothing here follows you around other websites.</li>
          <li>Apart from the visitor statistics described above, we use no third-party tracking.</li>
          <li>We do not ask for accounts, passwords, or anything we don’t need.</li>
        </ul>

        <h2>Moderation</h2>
        <p class="post-text">Every comment and reply is read by Bob before it appears in public. Until then, a
          faded copy of your own comment is visible only to you, on your own device.</p>

        <h2>Keeping and removing your data</h2>
        <p class="post-text">Comments and messages are kept for as long as the site runs. If you would like something
          you posted removed, just ask via the <a href="/contact.php">Contact page</a> and we will take care of it.</p>

        <h2>123Greetings</h2>
        <p class="post-text"><?= e(SITE_NAME) ?> is powered by 123Greetings. If you follow a link from our footer to a
          123Greetings website, your visit there is covered by that site’s own privacy policy, not this one.</p>

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
