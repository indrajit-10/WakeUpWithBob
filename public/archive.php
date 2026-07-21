<?php
/**
 * The Archive  (  /archive.php  )  — the public, read-only index of every morning.
 * A browsable calendar of past questions, grouped by the month they were posted.
 * (The public counterpart to the admin post-history.)
 */
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

ensure_session(); // start the session before any output so csrf_field() can set its cookie

// --- pagination: about 15 mornings per page, newest first ---
$perPage    = 15;
$totalPosts = (int) $pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn();
$totalPages = max(1, (int) ceil($totalPosts / $perPage));

$page   = max(1, (int) ($_GET['page'] ?? 1));
$page   = min($page, $totalPages);                 // clamp into [1, totalPages]
$offset = ($page - 1) * $perPage;

// Order by date so the "Month YYYY" groups always come out newest-first and in order.
$stmt = $pdo->prepare(
    'SELECT id, post_number, title, created_at, like_count, comment_count
     FROM questions ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?'
);
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$questions = $stmt->fetchAll();

// group this page's rows under "Month YYYY" headings (rows already arrive newest-first)
$months = [];
foreach ($questions as $q) {
    $key = date('F Y', strtotime($q['created_at']));
    $months[$key][] = $q;
}

$pageTitle = 'Archive · ' . SITE_NAME;
require __DIR__ . '/../app/views/header.php';
?>
<div class="app">
  <div class="shell">

    <nav class="left">
      <a class="nav" href="/"><svg class="ico"><use href="#i-home"/></svg>Home</a>
      <a class="nav active" href="/archive.php"><svg class="ico"><use href="#i-clock"/></svg>Archive</a>
      <div class="rail-sep"></div>
      <div class="rail-label">Community</div>
      <a class="nav" href="/about.php"><svg class="ico"><use href="#i-info"/></svg>About Bob</a>
    </nav>

    <main class="center">
      <div class="feedhead">
        <b>Archive</b>
        <span class="muted"><?= (int) $totalPosts ?> morning<?= $totalPosts === 1 ? '' : 's' ?> so far</span>
      </div>

      <?php if (!$questions): ?>
        <div class="empty">
          Nothing in the archive yet. Bob’s first question arrives with tomorrow’s coffee.
        </div>
      <?php else: ?>
        <?php foreach ($months as $month => $rows): ?>
          <div class="arch-month"><svg class="ico ico-sm"><use href="#i-clock"/></svg><?= e($month) ?></div>
          <ul class="post-history">
            <?php foreach ($rows as $q): ?>
              <li>
                <span class="post-num">#<?= (int) ($q['post_number'] ?: $q['id']) ?></span>
                <div class="post-hist-main">
                  <a class="post-hist-title" href="/question.php?id=<?= (int) $q['id'] ?>"><?= e($q['title']) ?></a>
                  <div class="post-hist-meta"><?= e(date('M j, Y', strtotime($q['created_at']))) ?> · <?= (int) $q['like_count'] ?> likes · <?= (int) $q['comment_count'] ?> comments</div>
                </div>
                <div class="post-hist-actions">
                  <a class="pill" href="/question.php?id=<?= (int) $q['id'] ?>"><svg class="ico ico-sm"><use href="#i-comment"/></svg>Read</a>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endforeach; ?>

        <?php if ($totalPages > 1): ?>
          <nav class="pagination">
            <?php if ($page > 1): ?>
              <a class="page-link" href="/archive.php?page=<?= $page - 1 ?>">← Newer</a>
            <?php else: ?>
              <span class="page-link disabled">← Newer</span>
            <?php endif; ?>

            <span class="page-info">Page <?= (int) $page ?> of <?= (int) $totalPages ?></span>

            <?php if ($page < $totalPages): ?>
              <a class="page-link" href="/archive.php?page=<?= $page + 1 ?>">Older →</a>
            <?php else: ?>
              <span class="page-link disabled">Older →</span>
            <?php endif; ?>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </main>

    <aside class="right">
      <?php include __DIR__ . '/../app/views/sidebar.php'; ?>
    </aside>

  </div>
</div>
<?php require __DIR__ . '/../app/views/footer.php'; ?>
