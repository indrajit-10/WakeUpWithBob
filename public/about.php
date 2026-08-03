<?php
/**
 * About Bob  (  /about.php  )  — who Bob is, the morning ritual, how the
 * community works, and the live numbers behind it.
 * Story first, quiet stats last. All queries here are fixed (no user input).
 */
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

ensure_session(); // start the session before any output so csrf_field() (right rail) can set its cookie

// --- the live numbers, straight from the DB (guard every null → 0) ---
$mornings = (int) $pdo->query('SELECT COUNT(*) FROM questions WHERE ' . published_sql())->fetchColumn();
// Only count replies on posts the public can actually see — a post moved to a future
// date takes its comments out of view too, and this counter must not betray them.
$replies  = (int) $pdo->query(
    'SELECT COUNT(*) FROM comments WHERE approved = 1 AND is_admin_reply = 0
       AND question_id IN (SELECT id FROM questions WHERE ' . published_sql() . ')'
)->fetchColumn();

$firstRaw = $pdo->query('SELECT MIN(created_at) FROM questions WHERE ' . published_sql())->fetchColumn();
$since    = $firstRaw ? date('M j, Y', db_time($firstRaw)) : null;   // e.g. "Jul 7, 2026"

$pageTitle = 'About Bob · ' . SITE_NAME;
$navActive = 'about';
require __DIR__ . '/../app/views/header.php';
?>
<div class="app">
  <div class="shell">

    <nav class="left">
      <?php include __DIR__ . '/../app/views/leftnav.php'; ?>
    </nav>

    <main class="center">
      <div class="feedhead">
        <b>About Bob</b>
      </div>

      <!-- why Bob writes -->
      <article class="post post--lead about-why">
        <div class="post-meta">
          <span class="avatar"><img class="logo" src="/assets/img/logo.svg" alt="" width="19" height="19"></span>
          <b class="byline">Bob</b>
          <span>· One question, every morning</span>
        </div>

        <h1 class="post-title lead-title">Why Bob writes</h1>

        <p class="post-text">Every morning, before the world becomes noisy, Bob sits down with a cup of coffee and asks one simple question:</p>

        <p class="about-question">What does it mean to be human?</p>

        <p class="post-text about-verse">
          Some mornings the answer begins with a birthday.<br>
          Some mornings with an old photograph.<br>
          Sometimes with a forgotten tradition, a scientific discovery, or a conversation between friends.
        </p>

        <p class="post-text">Bob believes that the quality of our lives is shaped less by extraordinary moments than by the ordinary relationships we often overlook.</p>

        <p class="post-text">Through <span class="brand-phrase">Five minutes with Bob</span>, he explores the small rituals, celebrations, memories and everyday experiences that quietly connect us to one another.</p>

        <p class="post-text about-verse">
          He won’t tell you how to live.<br>
          He won’t offer ten-step formulas or productivity hacks.
        </p>

        <p class="post-text">Instead, he’ll leave you with a thought, a question, and perhaps the gentle reminder of someone who matters.</p>

        <p class="post-text about-verse">
          If, after spending five minutes together, you think of a person you haven’t spoken to in a while…<br>
          or appreciate someone a little more…<br>
          or decide to reach out…
        </p>

        <p class="post-text">then today’s conversation was worthwhile.</p>

        <p class="about-welcome">Welcome.<br><span class="about-welcome-sub">The coffee’s ready.</span></p>
      </article>

      <!-- the quiet numbers, as a footer strip -->
      <div class="statstrip" style="display:none">
        <div class="stat">
          <span class="stat-num"><?= e(number_format($mornings)) ?></span>
          <span class="stat-label"><?= $mornings === 1 ? 'morning' : 'mornings' ?> so far</span>
        </div>
        <div class="stat">
          <span class="stat-num"><?= e(number_format($replies)) ?></span>
          <span class="stat-label"><?= $replies === 1 ? 'reply' : 'replies' ?> shared</span>
        </div>
        <?php if ($since !== null): ?>
          <div class="stat stat--since">
            <span class="stat-num stat-date"><?= e($since) ?></span>
            <span class="stat-label">since it all began</span>
          </div>
        <?php endif; ?>
      </div>
    </main>

    <aside class="right">
      <?php include __DIR__ . '/../app/views/sidebar.php'; ?>
    </aside>

  </div>
</div>
<?php require __DIR__ . '/../app/views/footer.php'; ?>