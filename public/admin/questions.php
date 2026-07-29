<?php
require __DIR__ . '/../../app/admin.php';
if (!admin_logged_in()) {
    redirect('/admin/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!empty($_POST['dismiss_id'])) {
        dismiss_reader_question(post_int('dismiss_id'));
        redirect('/admin/questions.php');
    }
}

$filter = (($_GET['filter'] ?? 'new') === 'all') ? 'all' : 'new';
$items = $filter === 'all'
    ? $pdo->query('SELECT * FROM reader_questions ORDER BY created_at DESC')->fetchAll()
    : $pdo->query("SELECT * FROM reader_questions WHERE status = 'new' ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Reader questions · ' . SITE_NAME;
$showSearch = false;
require __DIR__ . '/../../app/views/header.php';
?>
<div class="app admin-app">
  <div class="admin-shell">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <main class="admin-main">
      <div class="admin-header">
        <div>
          <h1>Reader questions</h1>
          <p class="muted">Questions readers sent in for Bob to ask. Private — never shown publicly.</p>
        </div>
      </div>
      <div class="admin-card">
        <div class="admin-card-head">
          <h2><?= $filter === 'all' ? 'All questions' : 'New questions' ?></h2>
          <div class="filter-tabs">
            <a class="filter-tab<?= $filter === 'new' ? ' active' : '' ?>" href="/admin/questions.php">New</a>
            <a class="filter-tab<?= $filter === 'all' ? ' active' : '' ?>" href="/admin/questions.php?filter=all">All</a>
          </div>
        </div>
        <?php if ($items): ?>
          <ul class="inbox-list">
            <?php foreach ($items as $it): ?>
              <li class="inbox-item<?= $it['status'] !== 'new' ? ' done' : '' ?>">
                <div class="inbox-body"><?= e($it['body']) ?></div>
                <div class="inbox-meta">
                  <?= e(time_ago($it['created_at'])) ?>
                  <?php if (!empty($it['email'])): ?> · <a href="mailto:<?= e($it['email']) ?>"><?= e($it['email']) ?></a><?php endif; ?>
                  <?php if ($it['status'] !== 'new'): ?> · <span class="inbox-status"><?= e($it['status']) ?></span><?php endif; ?>
                </div>
                <?php if ($it['status'] === 'new'): ?>
                  <form method="post" action="/admin/questions.php" class="inbox-actions">
                    <?= csrf_field() ?>
                    <input type="hidden" name="dismiss_id" value="<?= (int) $it['id'] ?>">
                    <button class="pill" type="submit">Mark handled</button>
                  </form>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="muted">Nothing here right now.</p>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../../app/views/footer.php'; ?>
