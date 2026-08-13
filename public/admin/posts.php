<?php
/**
 * All posts  (  /admin/posts.php  )  — the paginated history of every morning,
 * with Edit / Preview / Delete on each row.
 *
 * Split out of /admin/, which is now the composer alone. Delete is handled here
 * rather than there so that removing a post leaves you on the same list AND the
 * same page number, instead of bouncing you to the top of the composer.
 */
require __DIR__ . '/../../app/admin.php';

if (!admin_logged_in()) {
    redirect('/admin/');
}

$pageTitle  = 'All posts · ' . SITE_NAME;
$showSearch = false;
$notice     = '';

$perPage = 10;
$page    = max(1, (int) ($_GET['page'] ?? 1));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!empty($_POST['delete_question_id'])) {
        delete_question(post_int('delete_question_id'));
        // Keep the reader on the page they were looking at. post_int() is used
        // because a "delete_question_id[]" array would otherwise cast to 1 and
        // delete the wrong post.
        redirect('/admin/posts.php?deleted=1&page=' . $page);
    }
    redirect('/admin/posts.php');
}

if (!empty($_GET['updated'])) {
    $notice = 'Your changes were saved.';
} elseif (!empty($_GET['deleted'])) {
    $notice = 'The post was deleted.';
}

$totalPosts = (int) $pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn();
$totalPages = max(1, (int) ceil($totalPosts / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    'SELECT id, post_number, title, body, created_at, comment_count
     FROM questions ORDER BY COALESCE(post_number, id) DESC, id DESC LIMIT ? OFFSET ?'
);
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$questions = $stmt->fetchAll();

require __DIR__ . '/../../app/views/header.php';
?>

<div class="app admin-app">
  <div class="admin-shell">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <main class="admin-main">
      <div class="admin-header">
        <div>
          <h1>All posts</h1>
          <p class="muted">Every morning, newest first. Edit, preview or remove any of them.</p>
        </div>
        <a class="btn-orange" href="/admin/">New post</a>
      </div>

      <?php if ($notice !== ''): ?>
        <div class="admin-msg admin-success"><?= e($notice) ?></div>
      <?php endif; ?>

      <div class="admin-card">
        <div class="admin-card-head">
          <h2>Posts</h2>
          <span class="muted"><?= (int) $totalPosts ?> total · newest first</span>
        </div>
        <?php if ($questions): ?>
          <ul class="post-history">
            <?php foreach ($questions as $q): ?>
              <?php $scheduled = is_scheduled($q['created_at']); ?>
              <li>
                <span class="post-num">#<?= (int) $q['post_number'] ?></span>
                <div class="post-hist-main">
                  <?php /* A scheduled post 404s on its public URL, so point its title at
                           the preview instead — otherwise clicking it reads
                           "Question not found." */ ?>
                  <a class="post-hist-title"
                     href="<?= $scheduled ? '/admin/preview.php?id=' . (int) $q['id'] : '/question.php?id=' . (int) $q['id'] ?>"
                     target="_blank"><?= e(post_label($q['title'], $q['body'], $q['post_number'] ?? null)) ?></a>
                  <div class="post-hist-meta">
                    <?= e(fmt_datetime($q['created_at'])) ?> · <?= (int) $q['comment_count'] ?> comments
                    <?php if ($scheduled): ?>
                      <span class="badge-sched">Scheduled</span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="post-hist-actions">
                  <a class="pill" href="/admin/?edit=<?= (int) $q['id'] ?>">Edit</a>
                  <a class="pill" href="/admin/preview.php?id=<?= (int) $q['id'] ?>" target="_blank">Preview</a>
                  <form method="post" action="/admin/posts.php?page=<?= (int) $page ?>" data-confirm="Delete post #<?= (int) $q['post_number'] ?>?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="delete_question_id" value="<?= (int) $q['id'] ?>">
                    <button class="admin-delete" type="submit">Delete</button>
                  </form>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>

          <?php if ($totalPages > 1): ?>
            <nav class="pagination">
              <a class="page-link<?= $page <= 1 ? ' disabled' : '' ?>" href="/admin/posts.php?page=<?= max(1, $page - 1) ?>">← Prev</a>
              <span class="page-info">Page <?= (int) $page ?> of <?= (int) $totalPages ?></span>
              <a class="page-link<?= $page >= $totalPages ? ' disabled' : '' ?>" href="/admin/posts.php?page=<?= min($totalPages, $page + 1) ?>">Next →</a>
            </nav>
          <?php endif; ?>
        <?php else: ?>
          <p class="muted">No posts yet. <a href="/admin/">Write the first one</a>.</p>
        <?php endif; ?>
      </div>

    </main>
  </div>
</div>
<?php require __DIR__ . '/../../app/views/footer.php'; ?>
