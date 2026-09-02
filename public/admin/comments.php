<?php
/**
 * Admin · Moderation  (  /admin/comments.php  )
 *   • Incoming     — comments waiting for Bob. Approve or reject; approving moves
 *                    them out of here and into "All comments".
 *   • All comments — every approved reader comment, grouped by post, each flagged
 *                    "Replied" (your reply is shown) or "Needs reply". Filter by
 *                    Needs reply / Replied / All.
 */
require __DIR__ . '/../../app/admin.php';

if (!admin_logged_in()) {
    redirect('/admin/');
}

// --- read the tab + filter safely (never re-read a possibly-missing key) ---
$tabs = ['incoming', 'all'];
$tab  = $_GET['tab'] ?? 'incoming';
if (!in_array($tab, $tabs, true)) {
    $tab = 'incoming';
}

$filters = ['needs', 'replied', 'all'];
$filter  = $_GET['filter'] ?? 'all';
if (!in_array($filter, $filters, true)) {
    $filter = 'all';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $backTab = $_POST['tab'] ?? 'incoming';
    if (!in_array($backTab, $tabs, true)) {
        $backTab = 'incoming';
    }
    $backFilter = $_POST['filter'] ?? 'all';
    if (!in_array($backFilter, $filters, true)) {
        $backFilter = 'all';
    }
    $back = '/admin/comments.php?tab=' . $backTab . ($backTab === 'all' ? '&filter=' . $backFilter : '');

    if (!empty($_POST['approve_comment_id'])) {
        approve_comment(post_int('approve_comment_id'));
        redirect($back);
    }
    if (!empty($_POST['reject_comment_id'])) {
        reject_comment(post_int('reject_comment_id'));
        redirect($back);
    }
    if (!empty($_POST['approve_all_question_id'])) {
        approve_all_for_question(post_int('approve_all_question_id'));
        redirect($back);
    }
    $replyBody = clip($_POST['reply_body'] ?? '', 5000);   // clip() safely handles non-string (array) input
    if (!empty($_POST['reply_to_comment_id']) && $replyBody !== '') {
        reply_to_comment(post_int('reply_to_comment_id'), $replyBody);
        redirect($back);
    }
    redirect($back);
}

/** Group a flat comment list into [question_id => ['number','title','items'=>[]]], newest post first. */
function group_by_post(array $rows): array {
    $groups = [];
    foreach ($rows as $r) {
        $qid = (int) $r['question_id'];
        if (!isset($groups[$qid])) {
            $groups[$qid] = ['id' => $qid, 'number' => $r['post_number'] ?: $qid, 'title' => $r['question_title'], 'body' => $r['question_body'] ?? '', 'items' => []];
        }
        $groups[$qid]['items'][] = $r;
    }
    return $groups;
}

$pendingCount = (int) $pdo->query('SELECT COUNT(*) FROM comments WHERE approved = 0')->fetchColumn();

$groups = [];
$needsCount = $repliedCount = $allCount = 0;
$bobByParent = [];

if ($tab === 'incoming') {
    $rows = $pdo->query(
        "SELECT c.*, q.title AS question_title, q.body AS question_body, q.post_number,
                p.author_name AS parent_author, p.body AS parent_body, p.is_admin_reply AS parent_is_admin,
                (SELECT COUNT(*) FROM comments r WHERE r.parent_id = c.id) AS child_count
         FROM comments c
         JOIN questions q ON c.question_id = q.id
         LEFT JOIN comments p ON c.parent_id = p.id
         WHERE c.approved = 0
         ORDER BY COALESCE(q.post_number, q.id) DESC, c.created_at ASC"
    )->fetchAll();
    $groups = group_by_post($rows);
} else { // all
    // every approved reader comment, with how many replies Bob has given it
    $rows = $pdo->query(
        "SELECT c.*, q.title AS question_title, q.body AS question_body, q.post_number,
                p.author_name AS parent_author, p.body AS parent_body, p.is_admin_reply AS parent_is_admin,
                (SELECT COUNT(*) FROM comments r WHERE r.parent_id = c.id AND r.is_admin_reply = 1 AND r.approved = 1) AS bob_replies
         FROM comments c
         JOIN questions q ON c.question_id = q.id
         LEFT JOIN comments p ON c.parent_id = p.id
         WHERE c.approved = 1 AND c.is_admin_reply = 0
         ORDER BY COALESCE(q.post_number, q.id) DESC, c.created_at ASC"
    )->fetchAll();

    // Bob's replies, indexed by the comment they answer, so we can show the thread
    foreach ($pdo->query("SELECT id, parent_id, body, created_at FROM comments WHERE is_admin_reply = 1 AND approved = 1 AND parent_id IS NOT NULL ORDER BY created_at ASC") as $r) {
        $bobByParent[(int) $r['parent_id']][] = $r;
    }

    $allCount = count($rows);
    foreach ($rows as $r) {
        ((int) $r['bob_replies'] > 0) ? $repliedCount++ : $needsCount++;
    }

    // apply the Needs / Replied / All filter
    if ($filter === 'needs') {
        $rows = array_values(array_filter($rows, static fn ($r) => (int) $r['bob_replies'] === 0));
    } elseif ($filter === 'replied') {
        $rows = array_values(array_filter($rows, static fn ($r) => (int) $r['bob_replies'] > 0));
    }
    $groups = group_by_post($rows);
}

$pageTitle  = 'Moderation · ' . SITE_NAME;
$showSearch = false;
require __DIR__ . '/../../app/views/header.php';

$tabLink = static function (string $key, string $label, string $current, int $badge = 0): string {
    $b = $badge > 0 ? ' <span class="admin-badge">' . $badge . '</span>' : '';
    return '<a class="filter-tab' . ($key === $current ? ' active' : '') . '" href="/admin/comments.php?tab=' . e($key) . '">' . e($label) . $b . '</a>';
};
$filterChip = static function (string $key, string $label, int $count, string $current): string {
    $c = ' <span class="mod-chip-n">' . $count . '</span>';
    return '<a class="filter-tab filter-tab--' . e($key) . ($key === $current ? ' active' : '') . '" href="/admin/comments.php?tab=all&filter=' . e($key) . '">' . e($label) . $c . '</a>';
};
?>
<div class="app admin-app">
  <div class="admin-shell">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <main class="admin-main">
      <div class="admin-header">
        <div>
          <h1>Moderation</h1>
          <p class="muted"><?= $tab === 'incoming'
            ? 'New comments waiting on you. Approve to publish (they move to “All comments”), or reject.'
            : 'Every published comment, grouped by post — see what you’ve answered and what still needs a reply.' ?></p>
        </div>
      </div>

      <div class="mod-tabs">
        <?= $tabLink('incoming', 'Incoming', $tab, $pendingCount) ?>
        <?= $tabLink('all', 'All comments', $tab) ?>
      </div>

      <?php if ($tab === 'all'): ?>
        <div class="mod-filters">
          <span class="mod-filters-label">Show:</span>
          <?= $filterChip('needs', 'Needs reply', $needsCount, $filter) ?>
          <?= $filterChip('replied', 'Replied', $repliedCount, $filter) ?>
          <?= $filterChip('all', 'All', $allCount, $filter) ?>
        </div>
      <?php endif; ?>

      <?php if (!$groups): ?>
        <div class="admin-card">
          <p class="muted">
            <?php if ($tab === 'incoming'): ?>Nothing waiting — the queue is clear. 🎉
            <?php elseif ($filter === 'needs'): ?>Nothing needs a reply right now — you’re all caught up. 🎉
            <?php elseif ($filter === 'replied'): ?>You haven’t replied to any comments yet.
            <?php else: ?>No published comments yet.<?php endif; ?>
          </p>
        </div>
      <?php endif; ?>

      <?php foreach ($groups as $g): $count = count($g['items']); ?>
        <section class="admin-card mod-post">
          <div class="mod-post-head">
            <a class="mod-post-title" href="/question.php?id=<?= (int) $g['id'] ?>" target="_blank">
              <span class="post-num">#<?= (int) $g['number'] ?></span> <?= e(post_label($g['title'], $g['body'] ?? '', $g['number'])) ?>
            </a>
            <?php if ($tab === 'incoming'): ?>
              <div class="mod-post-tools">
                <span class="mod-count"><?= $count ?> waiting</span>
                <form method="post" action="/admin/comments.php">
                  <?= csrf_field() ?>
                  <input type="hidden" name="tab" value="incoming">
                  <input type="hidden" name="approve_all_question_id" value="<?= (int) $g['id'] ?>">
                  <button class="pill mod-approve-all" type="submit">✓ Approve all <?= $count ?></button>
                </form>
              </div>
            <?php endif; ?>
          </div>

          <ul class="mod-list">
            <?php foreach ($g['items'] as $c):
                $cid     = (int) $c['id'];
                $replied = $tab === 'all' && (int) $c['bob_replies'] > 0; ?>
              <li class="mod-item<?= $tab === 'all' ? ($replied ? ' mod-item--done' : ' mod-item--todo') : '' ?>">

                <?php if (!empty($c['parent_id'])): ?>
                  <div class="mod-context">
                    ↳ reply to <b><?= e($c['parent_author'] ?: ((int) $c['parent_is_admin'] === 1 ? 'Bob' : 'a reader')) ?></b>:
                    <span class="mod-context-quote">“<?= e(mb_strimwidth((string) $c['parent_body'], 0, 110, '…')) ?>”</span>
                  </div>
                <?php endif; ?>

                <div class="mod-head">
                  <b><?= e($c['author_name'] ?: 'A reader') ?></b>
                  <span class="muted" title="<?= e(fmt_datetime($c['created_at'])) ?>">· <?= e(time_ago($c['created_at'])) ?></span>
                  <span class="mod-time"><?= e(fmt_datetime($c['created_at'])) ?></span>
                  <?php if ($tab === 'all'): ?>
                    <span class="mod-status <?= $replied ? 'mod-status--done' : 'mod-status--todo' ?>">
                      <?= $replied ? '✓ Replied' : 'Needs reply' ?>
                    </span>
                  <?php endif; ?>
                </div>
                <div class="mod-body"><?= e($c['body']) ?></div>

                <?php if ($tab === 'incoming'): ?>
                  <div class="mod-actions">
                    <form method="post" action="/admin/comments.php">
                      <?= csrf_field() ?>
                      <input type="hidden" name="tab" value="incoming">
                      <input type="hidden" name="approve_comment_id" value="<?= $cid ?>">
                      <button class="mod-btn mod-btn--approve" type="submit">✓ Approve</button>
                    </form>
                    <?php
                      /* Rejecting is a hard DELETE with no undo, and the Reject button sits
                         right beside Approve — so it asks first. app.js's data-confirm
                         handler cancels the submit unless the dialog is accepted.
                         parent_id is ON DELETE CASCADE, so rejecting a comment that has
                         replies takes them too, including ones already published. The
                         message says so rather than letting that happen quietly. */
                      $whoC   = $c['author_name'] ?: 'this reader';
                      $kids   = (int) ($c['child_count'] ?? 0);
                      $confirm = $kids > 0
                        ? "Are you sure? {$whoC}'s comment and " . $kids . ' repl' . ($kids === 1 ? 'y' : 'ies')
                          . ' underneath it will be deleted permanently. This cannot be undone.'
                        : "Are you sure? {$whoC}'s comment will be deleted permanently. This cannot be undone.";
                    ?>
                    <form method="post" action="/admin/comments.php" data-confirm="<?= e($confirm) ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="tab" value="incoming">
                      <input type="hidden" name="reject_comment_id" value="<?= $cid ?>">
                      <button class="mod-btn mod-btn--reject" type="submit">Reject</button>
                    </form>
                  </div>
                <?php else: ?>
                  <?php if ($replied): ?>
                    <div class="mod-thread">
                      <?php foreach ($bobByParent[$cid] as $r): ?>
                        <div class="mod-bobreply">
                          <div class="mod-bobreply-head"><span class="tag-bob">Bob</span> <span class="muted">· <?= e(time_ago($r['created_at'])) ?></span></div>
                          <div class="mod-bobreply-body"><?= e($r['body']) ?></div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                  <div class="mod-actions">
                    <button class="mod-btn mod-btn--reply" type="button"
                            data-toggle="modreply-<?= $cid ?>" data-toggle-class="open">
                      <?= $replied ? 'Reply again' : 'Reply as Bob' ?>
                    </button>
                  </div>
                  <form id="modreply-<?= $cid ?>" class="mod-reply<?= $replied ? '' : ' open' ?>" method="post" action="/admin/comments.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tab" value="all">
                    <input type="hidden" name="filter" value="<?= e($filter) ?>">
                    <input type="hidden" name="reply_to_comment_id" value="<?= $cid ?>">
                    <textarea name="reply_body" rows="2" placeholder="Reply as Bob…" required></textarea>
                    <button class="btn-orange" type="submit">Post reply as Bob</button>
                  </form>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endforeach; ?>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../../app/views/footer.php'; ?>
