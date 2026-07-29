<?php
/**
 * Admin · Stats  (  /admin/stats.php  )
 * At-a-glance totals plus a per-post breakdown of comments.
 */
require __DIR__ . '/../../app/admin.php';

if (!admin_logged_in()) {
    redirect('/admin/');
}

// --- overall totals ---
$totPosts    = (int) $pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn();
$totApproved = (int) $pdo->query('SELECT COUNT(*) FROM comments WHERE approved = 1')->fetchColumn();
$totPending  = (int) $pdo->query('SELECT COUNT(*) FROM comments WHERE approved = 0')->fetchColumn();

$avgCmts = $totPosts ? round($totApproved / $totPosts, 1) : 0;

// --- per-post breakdown, with a whitelisted sort ---
$sort = $_GET['sort'] ?? 'recent';
$orderBy = match ($sort) {
    'comments' => 'q.comment_count DESC',
    default    => 'COALESCE(q.post_number, q.id) DESC',   // recent
};
$sort = in_array($sort, ['comments', 'recent'], true) ? $sort : 'recent';

// pending comments per post, resolved in one grouped query
$pendingByPost = [];
foreach ($pdo->query('SELECT question_id, COUNT(*) AS n FROM comments WHERE approved = 0 GROUP BY question_id') as $row) {
    $pendingByPost[(int) $row['question_id']] = (int) $row['n'];
}

$rows = $pdo->query(
    "SELECT q.id, q.post_number, q.title, q.body, q.created_at, q.comment_count
     FROM questions q ORDER BY $orderBy"
)->fetchAll();

$sortLink = static function (string $key, string $label, string $current): string {
    $active = $key === $current ? ' active' : '';
    return '<a class="stats-sort' . $active . '" href="/admin/stats.php?sort=' . e($key) . '">' . e($label) . '</a>';
};

$pageTitle = 'Stats · ' . SITE_NAME;
$showSearch = false;
require __DIR__ . '/../../app/views/header.php';
?>
<div class="app admin-app">
  <div class="admin-shell">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <main class="admin-main">
      <div class="admin-header">
        <div>
          <h1>Stats</h1>
          <p class="muted">How every morning is landing — comments at a glance.</p>
        </div>
      </div>

      <!-- headline totals -->
      <div class="stat-tiles">
        <div class="stat-tile"><span class="stat-tile-num"><?= e(number_format($totPosts)) ?></span><span class="stat-tile-label">Posts</span></div>
        <div class="stat-tile"><span class="stat-tile-num"><?= e(number_format($totApproved)) ?></span><span class="stat-tile-label">Comments</span></div>
        <div class="stat-tile"><span class="stat-tile-num"><?= e(number_format($totPending)) ?></span><span class="stat-tile-label">Awaiting approval</span></div>
        <div class="stat-tile"><span class="stat-tile-num"><?= e($avgCmts) ?></span><span class="stat-tile-label">Avg comments / post</span></div>
      </div>

      <div class="admin-card">
        <div class="admin-card-head">
          <h2>Per-post breakdown</h2>
          <div class="stats-sorts">
            Sort:
            <?= $sortLink('recent', 'Newest', $sort) ?>
            <?= $sortLink('comments', 'Most discussed', $sort) ?>
          </div>
        </div>

        <?php if ($rows): ?>
          <div class="stats-scroll">
            <table class="stats-table">
              <thead>
                <tr><th>#</th><th>Post</th><th class="num">Comments</th><th class="num">Pending</th></tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $q):
                    $qid = (int) $q['id'];
                    $pending = $pendingByPost[$qid] ?? 0; ?>
                  <tr>
                    <td class="post-num">#<?= (int) ($q['post_number'] ?: $q['id']) ?></td>
                    <td>
                      <a class="stats-title" href="/question.php?id=<?= $qid ?>"><?= e(post_label($q['title'], $q['body'], $q['post_number'] ?? null)) ?></a>
                      <div class="stats-date"><?= e(date('M j, Y', db_time($q['created_at']))) ?></div>
                    </td>
                    <td class="num"><?= (int) $q['comment_count'] ?></td>
                    <td class="num"><?= $pending > 0 ? '<span class="stats-pending">' . $pending . '</span>' : '<span class="stats-zero">0</span>' ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="muted">No posts yet — publish a morning question and its numbers will show up here.</p>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../../app/views/footer.php'; ?>
