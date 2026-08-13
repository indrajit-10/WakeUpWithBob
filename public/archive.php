<?php
/**
 * The Archive  (  /archive.php  )  — the public, read-only index of every morning,
 * browsed one month at a time. Mornings drop off the home feed after a week and
 * live here forever. Jump to any month, or step through with the arrows.
 */
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

ensure_session(); // start the session before any output so csrf_field() can set its cookie

$totalPosts = (int) $pdo->query('SELECT COUNT(*) FROM questions WHERE ' . published_sql())->fetchColumn();

// Every month that has at least one morning, newest first, with its post count.
// Grouped per row in PHP so each post's month is resolved with ITS OWN timezone
// offset — a single SQL offset would misfile posts from the other DST season.
$allDates = $pdo->query('SELECT created_at FROM questions WHERE ' . published_sql())
    ->fetchAll(PDO::FETCH_COLUMN);
$months = [];
foreach (count_by_local_month($allDates) as $ym => $n) {
    $months[] = ['ym' => $ym, 'n' => $n];
}

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
    // exact UTC bounds for the selected LOCAL month (DST-safe)
    $range = local_month_utc_range($current);
    if ($range !== null) {
        $stmt = $pdo->prepare(
            "SELECT id, post_number, title, body, created_at, comment_count
             FROM questions WHERE created_at >= ? AND created_at < ? AND " . published_sql()
             . " ORDER BY created_at DESC, id DESC"
        );
        $stmt->execute([$range[0], $range[1]]);
        $posts = $stmt->fetchAll();
    }
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
        <span class="muted"><?= (int) $totalPosts ?> Edition<?= $totalPosts === 1 ? '' : 's' ?></span>
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
                <a class="post-hist-title" href="/question.php?id=<?= (int) $q['id'] ?>"><?= e(post_label($q['title'], $q['body'], $q['post_number'] ?? null)) ?></a>
                <div class="post-hist-meta"><?= e(date('M j, Y', db_time($q['created_at']))) ?> · <?= (int) $q['comment_count'] ?> comments</div>
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
