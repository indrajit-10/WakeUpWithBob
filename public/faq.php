<?php
/**
 * FAQ  (  /faq.php  )  — the common questions about how Wake up with Bob works.
 * Static content; uses native <details> accordions so it needs no JavaScript.
 */
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

ensure_session(); // start the session before any output so csrf_field() (right rail) can set its cookie

// The Q&A, kept here so the markup below stays tidy.
$faqs = [
    [
        'q' => 'Who is Bob, and what is this?',
        'a' => 'Wake up with Bob is a small community built around a simple idea: every morning, Bob posts one question worth thinking about over your coffee. You read it, and if you feel like it, you tell the room what you think. That is the whole thing — one question a day, and a crowd of people answering it together.',
    ],
    [
        'q' => 'Do I need an account to join in?',
        'a' => 'No. There are no accounts, no passwords, and no sign-up. Just open the site, read the morning question, and reply. You can add a name if you like — we will remember it on this device for next time — but even that is optional.',
    ],
    [
        'q' => 'Why doesn’t my comment show up right away?',
        'a' => 'Every comment and every reply is read by Bob before it appears in public. Right after you post, you will see a faded copy of your own comment marked “waiting for Bob to read it” — that copy is only visible to you, and it turns into a normal comment once Bob approves it. This keeps the threads kind and on-topic.',
    ],
    [
        'q' => 'Can I reply to a reply?',
        'a' => 'Yes. Threads are fully nested — you can reply to the question, to any comment, or to a reply of a reply, as deep as the conversation goes. Every reply is held for Bob’s approval, the same as top-level comments.',
    ],
    [
        'q' => 'Can I edit or remove something I posted?',
        'a' => 'Not from your side yet — comments are held for approval, so if you need something changed or taken down, use the Contact page and Bob will sort it out.',
    ],
    [
        'q' => 'How do I suggest a question for Bob to ask?',
        'a' => 'Use the “Ask Bob a question” box in the right-hand sidebar on any page. It goes straight to Bob’s desk — privately, never posted publicly — and he reads every one.',
    ],
    [
        'q' => 'Is it free?',
        'a' => 'Completely. Wake up with Bob is free to read and free to join in.',
    ],
    [
        'q' => 'What is 123 Greetings’ part in this?',
        'a' => 'Wake up with Bob is powered by 123 Greetings — the same folks who have spent years helping people say the small, warm things. You will find links to the wider 123 Greetings family down in the footer.',
    ],
    [
        'q' => 'How is my information used?',
        'a' => 'Only to run the site — showing your comment once approved, remembering the name you last used on this device, and letting Bob reply to a question or note you sent in. We do not sell it or use third-party ad trackers. The full details are on the Privacy page.',
    ],
];

$pageTitle = 'FAQ · ' . SITE_NAME;
require __DIR__ . '/../app/views/header.php';
?>
<div class="app">
  <div class="shell">

    <nav class="left">
      <?php include __DIR__ . '/../app/views/leftnav.php'; ?>
    </nav>

    <main class="center">
      <div class="feedhead">
        <b>Frequently asked questions</b>
      </div>

      <div class="faq-list">
        <?php foreach ($faqs as $i => $item): ?>
          <details class="faq-item"<?= $i === 0 ? ' open' : '' ?>>
            <summary class="faq-q"><?= e($item['q']) ?><span class="faq-chev" aria-hidden="true"></span></summary>
            <div class="faq-a"><?= e($item['a']) ?></div>
          </details>
        <?php endforeach; ?>
      </div>

      <div class="faq-more">
        Still wondering something? <a href="/contact.php">Send us a note</a> — a real person reads it.
      </div>
    </main>

    <aside class="right">
      <?php include __DIR__ . '/../app/views/sidebar.php'; ?>
    </aside>

  </div>
</div>
<?php require __DIR__ . '/../app/views/footer.php'; ?>
