<?php
/**
 * Admin · Subscribers  (  /admin/subscribers.php  )
 * Everyone who signed up through the "Subscribe To Read More" popup or the
 * sidebar box, newest first. Download a day (or any date range, or everything)
 * as a CSV file that opens in Excel, to hand to the mailing team. Deleting a
 * row here only removes it from this list — not from any copy already sent on.
 */
require __DIR__ . '/../../app/admin.php';

if (!admin_logged_in()) {
    redirect('/admin/');
}

$perPage = 100;
$page    = max(1, (int) ($_GET['page'] ?? 1));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!empty($_POST['delete_id'])) {
        $pdo->prepare('DELETE FROM subscribers WHERE id = ?')->execute([post_int('delete_id')]);
    }
    $p = post_int('page');
    redirect('/admin/subscribers.php' . ($p > 1 ? '?page=' . $p : ''));
}

/** A 'YYYY-MM-DD' from the query string, or null when missing / not a real date. */
$dateParam = static function (string $key): ?string {
    $v = $_GET[$key] ?? '';
    if (!is_string($v) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})\z/', $v, $m)) {   // \z: no trailing newline
        return null;
    }
    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $v : null;
};

/** "Post #25" / "Sidebar" — post_number is looked up live, so it stays right after a renumber. */
$fromLabel = static function (array $r): string {
    if ($r['source'] === 'sidebar') {
        return 'Sidebar';
    }
    return $r['post_number'] !== null ? 'Post #' . (int) $r['post_number'] : 'Post';
};

$select = 'SELECT s.id, s.email, s.source, s.created_at, q.post_number
           FROM subscribers s LEFT JOIN questions q ON q.id = s.question_id AND s.question_id > 0';

$today = date('Y-m-d');

// ---- CSV download ------------------------------------------------------------
if (($_GET['export'] ?? '') === 'csv') {
    if (!empty($_GET['all'])) {
        $rows     = $pdo->query("$select ORDER BY s.created_at ASC, s.id ASC")->fetchAll();
        $filename = 'subscribers-all-' . $today . '.csv';
    } else {
        $from = $dateParam('from') ?? $today;
        $to   = $dateParam('to')   ?? $from;
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $start = local_day_utc_range($from);
        $end   = local_day_utc_range($to);
        $stmt  = $pdo->prepare("$select WHERE s.created_at >= ? AND s.created_at < ? ORDER BY s.created_at ASC, s.id ASC");
        $stmt->execute([$start[0] ?? '9999', $end[1] ?? '9999']);
        $rows     = $stmt->fetchAll();
        $filename = 'subscribers-' . $from . ($to !== $from ? '-to-' . $to : '') . '.csv';
    }

    // One CSV field. A leading = + - @ (or tab / CR) would run as a formula in
    // Excel; subscribe.php already refuses such addresses, this is the backstop.
    $cell = static function (string $v): string {
        if ($v !== '' && strpbrk($v[0], "=+-@\t\r") !== false) {
            $v = "'" . $v;
        }
        return '"' . str_replace('"', '""', $v) . '"';
    };

    // db.php buffers every page; drop that buffer so the file is exactly the CSV.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store');

    echo "\xEF\xBB\xBF";   // UTF-8 marker, so Excel doesn't garble the text
    echo implode(',', array_map($cell, ['Sl No', 'Email', 'Date', 'Time (' . date('T') . ')', 'Subscribed from'])) . "\r\n";
    $n = 0;
    foreach ($rows as $r) {
        $ts = db_time((string) $r['created_at']);
        echo implode(',', array_map($cell, [
            (string) ++$n,
            (string) $r['email'],
            date('j M Y', $ts),
            date('g:i a', $ts),
            $fromLabel($r),
        ])) . "\r\n";
    }
    exit;
}

// ---- the page ----------------------------------------------------------------
$total      = (int) $pdo->query('SELECT COUNT(*) FROM subscribers')->fetchColumn();
$todayRange = local_day_utc_range($today) ?? ['9999', '9999'];
$weekRange  = local_period_utc_range(date('Y-m-d', strtotime('-6 days')) . ' 00:00:00', '+7 days') ?? ['9999', '9999'];
$countSince = static function (array $range) use ($pdo): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM subscribers WHERE created_at >= ? AND created_at < ?');
    $stmt->execute([$range[0], $range[1]]);
    return (int) $stmt->fetchColumn();
};
$todayCount = $countSince($todayRange);
$weekCount  = $countSince($weekRange);

$totalPages = max(1, (int) ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;
$stmt = $pdo->prepare("$select ORDER BY s.created_at DESC, s.id DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$pageTitle  = 'Subscribers · ' . SITE_NAME;
$showSearch = false;
require __DIR__ . '/../../app/views/header.php';
?>
<div class="app admin-app">
  <div class="admin-shell">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <main class="admin-main">
      <div class="admin-header">
        <div>
          <h1>Subscribers</h1>
          <p class="muted">Everyone who signed up with the Subscribe button or the sidebar box. No email is sent from the site — download the list and send it to the mailing team.</p>
        </div>
      </div>

      <div class="stat-tiles">
        <div class="stat-tile"><span class="stat-tile-num"><?= e(number_format($total)) ?></span><span class="stat-tile-label">Total subscribers</span></div>
        <div class="stat-tile"><span class="stat-tile-num"><?= e(number_format($todayCount)) ?></span><span class="stat-tile-label">Today</span></div>
        <div class="stat-tile"><span class="stat-tile-num"><?= e(number_format($weekCount)) ?></span><span class="stat-tile-label">Last 7 days</span></div>
      </div>

      <div class="admin-card">
        <h2>Download</h2>
        <p>A CSV file — it opens in Excel. Pick a day (or a range) and download; both dates are set to today.</p>
        <form class="sub-export" method="get" action="/admin/subscribers.php">
          <input type="hidden" name="export" value="csv">
          <label>From <input type="date" name="from" value="<?= e($today) ?>" max="<?= e($today) ?>" required></label>
          <label>To <input type="date" name="to" value="<?= e($today) ?>" max="<?= e($today) ?>" required></label>
          <button class="btn-orange" type="submit">Download</button>
          <a class="pill" href="/admin/subscribers.php?export=csv&amp;all=1">Download all</a>
        </form>
      </div>

      <div class="admin-card">
        <div class="admin-card-head">
          <h2>All subscribers</h2>
          <span class="muted"><?= e(number_format($total)) ?> total</span>
        </div>
        <?php if ($rows): ?>
          <div class="stats-scroll">
            <table class="stats-table sub-table">
              <thead>
                <tr><th class="num">Sl No</th><th>Email</th><th>Date</th><th>From</th><th>Action</th></tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $i => $r): $ts = db_time((string) $r['created_at']); ?>
                  <tr>
                    <td class="num"><?= $offset + $i + 1 ?></td>
                    <td class="sub-email"><?= e($r['email']) ?></td>
                    <td><?= e(date('j M Y', $ts)) ?><div class="stats-date"><?= e(date('g:i a', $ts)) ?></div></td>
                    <td><?= e($fromLabel($r)) ?></td>
                    <td>
                      <form method="post" action="/admin/subscribers.php"
                            data-confirm="<?= e('Are you sure? ' . $r['email'] . ' will be removed from the subscriber list. This cannot be undone.') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete_id" value="<?= (int) $r['id'] ?>">
                        <input type="hidden" name="page" value="<?= (int) $page ?>">
                        <button class="mod-btn mod-btn--reject" type="submit">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php if ($totalPages > 1): ?>
            <nav class="pagination">
              <a class="page-link<?= $page <= 1 ? ' disabled' : '' ?>" href="/admin/subscribers.php?page=<?= max(1, $page - 1) ?>">← Prev</a>
              <span class="page-info">Page <?= (int) $page ?> of <?= (int) $totalPages ?></span>
              <a class="page-link<?= $page >= $totalPages ? ' disabled' : '' ?>" href="/admin/subscribers.php?page=<?= min($totalPages, $page + 1) ?>">Next →</a>
            </nav>
          <?php endif; ?>
        <?php else: ?>
          <p class="muted">No subscribers yet.</p>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../../app/views/footer.php'; ?>
