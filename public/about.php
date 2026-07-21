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
$mornings = (int) $pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn();
$replies  = (int) $pdo->query('SELECT COUNT(*) FROM comments WHERE approved = 1 AND is_admin_reply = 0')->fetchColumn();
$qLikes   = (int) $pdo->query('SELECT COALESCE(SUM(like_count), 0) FROM questions')->fetchColumn();
$cLikes   = (int) $pdo->query('SELECT COALESCE(SUM(like_count), 0) FROM comments')->fetchColumn();
$likes    = $qLikes + $cLikes;

$firstRaw = $pdo->query('SELECT MIN(created_at) FROM questions')->fetchColumn();
$since    = $firstRaw ? date('M j, Y', strtotime($firstRaw)) : null;   // e.g. "Jul 7, 2026"

$pageTitle = 'About Bob · ' . SITE_NAME;
require __DIR__ . '/../app/views/header.php';
?>
<div class="app">
  <div class="shell">

    <nav class="left">
      <a class="nav" href="/"><svg class="ico"><use href="#i-home"/></svg>Home</a>
      <a class="nav" href="/archive.php"><svg class="ico"><use href="#i-clock"/></svg>Archive</a>
      <div class="rail-sep"></div>
      <div class="rail-label">Community</div>
      <a class="nav active" href="/about.php"><svg class="ico"><use href="#i-info"/></svg>About Bob</a>
    </nav>

    <main class="center">
      <div class="feedhead">
        <b>About Bob</b>
      </div>

      <!-- the story: who Bob is, and why one question every morning -->
      <article class="post post--lead">
        <div class="post-meta">
          <span class="avatar"><img class="logo" src="/assets/img/logo.svg" alt="" width="19" height="19"></span>
          <b class="byline">Bob</b>
          <span>· One question, every morning</span>
        </div>

        <h1 class="post-title lead-title">One question every morning, and a whole town that answers.</h1>
        <p class="post-text">Most mornings start the same way for most of us — coffee, a quiet minute, and a head still half-asleep. Bob figured that quiet minute was the perfect time for one good question. Not the news, not a to-do list. Just something small and human worth turning over while the kettle warms up.</p>
        <p class="post-text">So that is the whole idea. Every morning Bob posts a single question — sometimes tender, sometimes silly, always the kind you would actually answer out loud. You read it with your coffee, you sit with it a moment, and if you feel like it, you tell the room what you think. The rest of us get to read along.</p>
        <p class="post-text">There are no accounts, no scores, no endless scroll. One question a day, a shared morning, and a small crowd of people thinking about the same little thing at the same time. That is Bob.</p>
      </article>

      <!-- how the little loop works -->
      <article class="post">
        <h2 class="about-h">How it works</h2>
        <ol class="hiw">
          <li class="hiw-step">
            <span class="hiw-num">1</span>
            <div class="hiw-body">
              <div class="hiw-h"><svg class="ico ico-sm"><use href="#i-ask"/></svg>Bob posts one question each morning.</div>
              <p>A single question lands on the feed with the day's first coffee. Just one — so it is easy to answer and easy to remember. Miss a day and it will be waiting in the <a href="/archive.php">archive</a>.</p>
            </div>
          </li>
          <li class="hiw-step">
            <span class="hiw-num">2</span>
            <div class="hiw-body">
              <div class="hiw-h"><svg class="ico ico-sm"><use href="#i-comment"/></svg>You like it, reply, and share.</div>
              <p>Say what you think, react to a reply that stuck with you, or pass the morning's question along to someone who would smile at it.</p>
            </div>
          </li>
          <li class="hiw-step">
            <span class="hiw-num">3</span>
            <div class="hiw-body">
              <div class="hiw-h"><svg class="ico ico-sm"><use href="#i-heart"/></svg>Bob reads every reply before it appears.</div>
              <p>Nothing goes up automatically. Each reply waits on Bob's desk until he has read it and waved it through — so the thread stays as kind as the morning it belongs to.</p>
            </div>
          </li>
        </ol>
      </article>

      <!-- what keeps the room a good place to be -->
      <article class="post">
        <h2 class="about-h">What we're going for</h2>
        <p class="post-text">There are no accounts and no scores here — just people showing up with their mornings. A few things keep it that way:</p>
        <ul class="about-values">
          <li><b>Kindness first.</b> Answer like you would talk to a neighbour over the fence.</li>
          <li><b>Real over clever.</b> An honest sentence beats a perfect one.</li>
          <li><b>Everyone's read.</b> Every reply passes Bob's desk, so the thread stays a good place to be.</li>
          <li><b>No pile-ons.</b> Disagree gently, or just scroll on by.</li>
        </ul>
      </article>

      <!-- powered by 123 Greetings -->
      <article class="post about-cobrand">
        <div class="about-cobrand-mark">
          <span class="avatar"><img class="logo" src="/assets/img/logo.svg" alt="" width="24" height="24"></span>
          <?= g123_logo('md') ?>
        </div>
        <p class="post-text">Wake up with Bob is brought to you by <b>123 Greetings</b> — the same folks who have been helping people say the small, warm things for years. Bob's morning question is just one more little way to start the day a bit brighter.</p>
      </article>

      <!-- a friendly nudge back to the feed -->
      <article class="post about-cta">
        <h2 class="about-h">Come say good morning</h2>
        <p class="post-text">Today's question is waiting on the feed — read it, and tell the room what you think.</p>
        <div class="about-cta-row">
          <a class="btn-orange about-cta-btn" href="/">Read this morning's question</a>
          <span class="tiny"><span class="dot"></span>Powered by <?= g123_logo() ?></span>
        </div>
        <p class="muted about-cta-note">Got one for Bob to ask? Use <b>Ask Bob a question</b> over in the sidebar — he reads every one.</p>
      </article>

      <!-- the quiet numbers, as a footer strip -->
      <div class="statstrip">
        <div class="stat">
          <span class="stat-num"><?= e(number_format($mornings)) ?></span>
          <span class="stat-label"><?= $mornings === 1 ? 'morning' : 'mornings' ?> so far</span>
        </div>
        <div class="stat">
          <span class="stat-num"><?= e(number_format($replies)) ?></span>
          <span class="stat-label"><?= $replies === 1 ? 'reply' : 'replies' ?> shared</span>
        </div>
        <div class="stat">
          <span class="stat-num"><?= e(number_format($likes)) ?></span>
          <span class="stat-label"><?= $likes === 1 ? 'like' : 'likes' ?> given</span>
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