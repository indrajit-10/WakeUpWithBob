<?php
/** Admin left nav. Shows a badge with the count of items needing attention. */
$active = basename($_SERVER['PHP_SELF']);
$pendingCount = (int) $pdo->query('SELECT COUNT(*) FROM comments WHERE approved = 0')->fetchColumn();
$newQuestions = (int) $pdo->query("SELECT COUNT(*) FROM reader_questions WHERE status = 'new'")->fetchColumn();
$newFeedback  = (int) $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'new'")->fetchColumn();
$badge = static fn (int $n): string => $n > 0 ? '<span class="admin-badge">' . $n . '</span>' : '';
?>
<aside class="admin-sidebar">
  <div class="admin-sidebar-header">Admin</div>
  <nav class="admin-nav">
    <a class="admin-nav-link<?= $active === 'index.php' ? ' active' : '' ?>" href="/admin/">Posts</a>
    <a class="admin-nav-link<?= $active === 'stats.php' ? ' active' : '' ?>" href="/admin/stats.php">Stats</a>
    <a class="admin-nav-link<?= $active === 'searches.php' ? ' active' : '' ?>" href="/admin/searches.php">User Search Data</a>
    <a class="admin-nav-link<?= $active === 'comments.php' ? ' active' : '' ?>" href="/admin/comments.php">Comments <?= $badge($pendingCount) ?></a>
    <a class="admin-nav-link<?= $active === 'questions.php' ? ' active' : '' ?> d-none" href="/admin/questions.php">Reader questions <?= $badge($newQuestions) ?></a>
    <a class="admin-nav-link<?= $active === 'feedback.php' ? ' active' : '' ?>" href="/admin/feedback.php">Contact us<?= $badge($newFeedback) ?></a>
    <a class="admin-nav-link<?= $active === 'settings.php' ? ' active' : '' ?>" href="/admin/settings.php">Settings</a>
  </nav>
          <form method="post" action="/admin/" style="margin-top: 25px;margin-left: 12px;">
            <?= csrf_field() ?>
            <input type="hidden" name="logout" value="1">
            <button class="pill" type="submit" style="background:var(--red); color: var(--card);line-height: 1; padding-bottom: 13px;">Log out</button>
          </form>

</aside>
