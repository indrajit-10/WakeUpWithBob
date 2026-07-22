<?php
/**
 * Contact  (  /contact.php  )  — a general "reach a human" page.
 * The message lands in the admin Feedback inbox, tagged [Contact] so Bob can
 * tell it apart from in-app feedback. Same CSRF + length caps as every form.
 */
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

ensure_session();

$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name    = clip($_POST['name'] ?? '', 80);
    $email   = clip($_POST['email'] ?? '', 120);
    $message = clip($_POST['message'] ?? '', 2000);

    if ($message !== '') {
        // Reuse the Feedback inbox; tag it so it's clearly a Contact submission.
        $composed = '[Contact] ' . ($name !== '' ? $name . ': ' : '') . $message;
        $stmt = $pdo->prepare('INSERT INTO feedback (body, email) VALUES (?, ?)');
        $stmt->execute([$composed, $email !== '' ? $email : null]);
        // (an email alert to Bob would fire here once SMTP is wired up)
        flash_set('Thanks for reaching out — your message is with Bob.');
    }
    redirect('/contact.php');
}

$pageTitle = 'Contact · ' . SITE_NAME;
require __DIR__ . '/../app/views/header.php';
?>
<div class="app">
  <div class="shell">

    <nav class="left">
      <?php include __DIR__ . '/../app/views/leftnav.php'; ?>
    </nav>

    <main class="center">
      <div class="feedhead">
        <b>Contact us</b>
      </div>

      <article class="post">
        <p class="post-text">Have a question, a worry, or something you would like taken down? Send Bob a note below and a real person will read it. If you would rather email, reach us at
          <a class="contact-mail" href="mailto:<?= e(CONTACT_EMAIL) ?>"><?= e(CONTACT_EMAIL) ?></a>.</p>
        <p class="muted contact-hint">Want to suggest a question for the morning feed instead? Use <b>Ask Bob a question</b> over in the sidebar.</p>

        <form class="contact-form" method="post" action="/contact.php">
          <?= csrf_field() ?>
          <div class="contact-row">
            <label><span>Your name (optional)</span>
              <input type="text" name="name" value="<?= e(remembered_name()) ?>" maxlength="80">
            </label>
            <label><span>Email (optional, if you’d like a reply)</span>
              <input type="email" name="email" maxlength="120">
            </label>
          </div>
          <label><span>Message</span>
            <textarea name="message" rows="6" required placeholder="What’s on your mind?"></textarea>
          </label>
          <div class="contact-bar">
            <span class="tiny"><span class="dot"></span>Goes privately to Bob — never posted publicly.</span>
            <button class="btn-orange" type="submit">Send message</button>
          </div>
        </form>
      </article>
    </main>

    <aside class="right">
      <?php include __DIR__ . '/../app/views/sidebar.php'; ?>
    </aside>

  </div>
</div>
<?php require __DIR__ . '/../app/views/footer.php'; ?>
