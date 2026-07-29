<?php
/**
 * Admin · User Search Data  (  /admin/searches.php  )
 * Every search readers ran — the query and how many results it returned —
 * laid out one day at a time, browsed by month. Days with no searches are
 * still shown, marked "No searches for the day". Anonymous (no IP, no name).
 */
require __DIR__ . '/../../app/admin.php';

if (!admin_logged_in()) {
    redirect('/admin/');
}

$totalSearches = (int) $pdo->query('SELECT COUNT(*) FROM searches')->fetchColumn();

// Months that have searches, newest first, with counts.
// per-row local grouping, so a search logged near local midnight cannot be
// bucketed into a month whose page then refuses to render it
$allSearchDates = $pdo->query('SELECT created_at FROM searches')->fetchAll(PDO::FETCH_COLUMN);
$months = [];
foreach (count_by_local_month($allSearchDates) as $ym => $n) {
    $months[] = ['ym' => $ym, 'n' => $n];
}
$monthKeys = array_column($months, 'ym');

// Always include the current month so 'today' is browsable even with no searches yet.
$curMonth = date('Y-m');
if (!in_array($curMonth, $monthKeys, true)) {
    array_unshift($monthKeys, $curMonth);
    array_unshift($months, ['ym' => $curMonth, 'n' => 0]);
}

// Which month are we viewing? A valid ?m=YYYY-MM, else the latest.
$current = $_GET['m'] ?? '';
if (!in_array($current, $monthKeys, true)) {
    $current = $monthKeys[0];
}

$idx      = array_search($current, $monthKeys, true);
$newerKey = ($idx !== false && $idx > 0) ? $monthKeys[$idx - 1] : null;
$olderKey = ($idx !== false && $idx < count($monthKeys) - 1) ? $monthKeys[$idx + 1] : null;

// All searches in the selected month, grouped by calendar day.
$range = local_month_utc_range($current) ?? ['9999-01-01 00:00:00', '9999-01-02 00:00:00'];
$stmt = $pdo->prepare(
    "SELECT query, result_count, created_at FROM searches
     WHERE created_at >= ? AND created_at < ? ORDER BY created_at DESC"
);
$stmt->execute([$range[0], $range[1]]);
$byDay = [];
foreach ($stmt->fetchAll() as $r) {
    $byDay[date('Y-m-d', db_time((string) $r['created_at']))][] = $r;
}

// Every day of the month, newest first. For the current month, stop at today.
$daysInMonth = (int) date('t', strtotime($current . '-01'));
$lastDay     = ($current === $curMonth) ? (int) date('j') : $daysInMonth;
$days = [];
for ($d = $lastDay; $d >= 1; $d--) {
    $days[] = sprintf('%s-%02d', $current, $d);
}

$monthLabel = static fn (string $ym): string => date('F Y', strtotime($ym . '-01'));

$pageTitle  = 'User Search Data · ' . SITE_NAME;
$showSearch = false;
require __DIR__ . '/../../app/views/header.php';
?>
<div class="app admin-app">
  <div class="admin-shell">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <main class="admin-main">
      <div class="admin-header">
        <div>
          <h1>User Search Data</h1>
          <p class="muted">What readers searched for and how many results came back — day by day. Anonymous: no IP, no name. <?= (int) $totalSearches ?> search<?= $totalSearches === 1 ? '' : 'es' ?> logged in total.</p>
        </div>
      </div>

      <div class="admin-card">
        <div class="arch-nav">
          <?php if ($newerKey !== null): ?>
            <a class="arch-arrow" href="/admin/searches.php?m=<?= e($newerKey) ?>" aria-label="Newer month">&larr;</a>
          <?php else: ?>
            <span class="arch-arrow disabled" aria-hidden="true">&larr;</span>
          <?php endif; ?>

          <label class="arch-jump">
            <span class="sr-only">Jump to month</span>
            <select data-navigate>
              <?php foreach ($months as $m): ?>
                <option value="/admin/searches.php?m=<?= e($m['ym']) ?>"<?= $m['ym'] === $current ? ' selected' : '' ?>><?= e($monthLabel($m['ym'])) ?> (<?= (int) $m['n'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </label>

          <?php if ($olderKey !== null): ?>
            <a class="arch-arrow" href="/admin/searches.php?m=<?= e($olderKey) ?>" aria-label="Older month">&rarr;</a>
          <?php else: ?>
            <span class="arch-arrow disabled" aria-hidden="true">&rarr;</span>
          <?php endif; ?>
        </div>

        <div class="srch-report">
          <?php foreach ($days as $day):
              $items = $byDay[$day] ?? []; ?>
            <div class="srch-day">
              <div class="srch-day-head">
                <span class="srch-day-date"><?= e(date('l, M j, Y', strtotime($day))) ?></span>
                <span class="srch-day-count"><?= count($items) ?> search<?= count($items) === 1 ? '' : 'es' ?></span>
              </div>
              <?php if ($items): ?>
                <ul class="srch-list">
                  <?php foreach ($items as $it): ?>
                    <li class="srch-item">
                      <span class="srch-q">“<?= e($it['query']) ?>”</span>
                      <span class="srch-meta"><?= (int) $it['result_count'] ?> result<?= (int) $it['result_count'] === 1 ? '' : 's' ?> · <?= e(date('g:i a', db_time((string) $it['created_at']))) ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <p class="srch-empty">No searches for the day.</p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../../app/views/footer.php'; ?>
