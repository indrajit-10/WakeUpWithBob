<?php
/**
 * The Archive  (  /archive.php  )  — the public, read-only index of every morning,
 * browsed one month at a time. Mornings drop off the home feed after a week and
 * live here forever. Jump to any month, or step through with the arrows.
 */
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

ensure_session(); // start the session before any output so csrf_field() can set its cookie

$totalPosts = (int) $pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn();

// Every month that has at least one morning, newest first, with its post count.
$months = $pdo->query(
    "SELECT strftime('%Y-%m', created_at) AS ym, COUNT(*) AS n
     FROM questions GROUP BY ym ORDER BY ym DESC"
)->fetchAll();

// Which month are we viewing? A valid ?m=YYYY-MM that exists, else the latest.
$monthKeys = array_column($months, 'ym');
$current   = $_GET['m'] ?? '';
if (!in_array($current, $monthKeys, true)) {
    $current = $monthKeys[0] ?? '';   // latest month (or '' when there are no posts)
}

// Neighbours for the prev/next arrows (the list runs newest → oldest).
$idx      = array_search($current, $monthKeys, true);
$newerKey = ($idx !== false && $idx > 0) ? $monthKeys[$idx - 1] : null;                       // more recent month
$olderKey = ($idx !== false && $idx < count($monthKeys) - 1) ? $monthKeys[$idx + 1] : null;   // older month

// Posts in the current month, newest first.
$posts = [];
if ($current !== '') {
    $stmt = $pdo->prepare(
        "SELECT id, post_number, title, created_at, comment_count
         FROM questions WHERE strftime('%Y-%m', created_at) = ? ORDER BY created_at DESC, id DESC"
    );
    $stmt->execute([$current]);
    $posts = $stmt->fetchAll();
}

$monthLabel = static fn (string $ym): string => date('F Y', strtotime($ym . '-01'));

$pageTitle = 'Archive · ' . SITE_NAME;
$navActive = 'archive';
require __DIR__ . '/../app/views/header.php';
?>
<div class="app">
  <div class="shell">

    <nav class="left">
      <?php include __DIR__ . '/../app/views/leftnav.php'; ?>
    </nav>

    <main class="center">
      <div class="feedhead">
        <b>Archive</b>
        <span class="muted"><?= (int) $totalPosts ?> morning<?= $totalPosts === 1 ? '' : 's' ?> so far</span>
      </div>

      <?php if (!$months): ?>
        <div class="empty">
          Nothing in the archive yet. Bob’s first question arrives with tomorrow’s coffee.
        </div>
      <?php else: ?>

        <div class="arch-nav">
          <?php if ($newerKey !== null): ?>
            <a class="arch-arrow" href="/archive.php?m=<?= e($newerKey) ?>" aria-label="Newer month">&larr;</a>
          <?php else: ?>
            <span class="arch-arrow disabled" aria-hidden="true">&larr;</span>
          <?php endif; ?>

          <label class="arch-jump">
            <span class="sr-only">Jump to month</span>
            <select data-navigate>
              <?php foreach ($months as $m): ?>
                <option value="/archive.php?m=<?= e($m['ym']) ?>"<?= $m['ym'] === $current ? ' selected' : '' ?>><?= e($monthLabel($m['ym'])) ?> (<?= (int) $m['n'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </label>

          <?php if ($olderKey !== null): ?>
            <a class="arch-arrow" href="/archive.php?m=<?= e($olderKey) ?>" aria-label="Older month">&rarr;</a>
          <?php else: ?>
            <span class="arch-arrow disabled" aria-hidden="true">&rarr;</span>
          <?php endif; ?>
        </div>

        <div class="arch-month"><svg class="ico ico-sm"><use href="#i-clock"/></svg><?= e($monthLabel($current)) ?></div>
        <ul class="post-history">
          <?php foreach ($posts as $q): ?>
            <li>
              <span class="post-num">#<?= (int) ($q['post_number'] ?: $q['id']) ?></span>
              <div class="post-hist-main">
                <a class="post-hist-title" href="/question.php?id=<?= (int) $q['id'] ?>"><?= e($q['title']) ?></a>
                <div class="post-hist-meta"><?= e(date('M j, Y', strtotime($q['created_at']))) ?> · <?= (int) $q['comment_count'] ?> comments</div>
              </div>
              <div class="post-hist-actions">
                <a class="pill" href="/question.php?id=<?= (int) $q['id'] ?>"><svg class="ico ico-sm"><use href="#i-comment"/></svg>Read</a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>

      <?php endif; ?>
    </main>

    <aside class="right">
      <?php include __DIR__ . '/../app/views/sidebar.php'; ?>
    </aside>

  </div>
</div>
<?php require __DIR__ . '/../app/views/footer.php'; ?>
