<?php
/**
 * The feed  (  /  )  — the first real page.
 * Reads questions from SQLite and renders a card for each one.
 */
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

ensure_session(); // start the session before any output so csrf_field() can set its cookie

// --- read the sort + search from the URL ($_GET) ---
$sort   = $_GET['sort'] ?? 'latest';
$search = trim($_GET['q'] ?? '');

$sortLabels = ['latest' => 'Latest', 'engaging' => 'Most engaging'];

$orderBy = match ($sort) {
    'engaging' => 'comment_count DESC, created_at DESC',   // most discussed first
    default    => 'created_at DESC',                        // latest
};

// --- fetch the questions ---
if ($search !== '') {
    // Search covers the TITLE + BODY of every morning (never comments), across all
    // time. Multi-word queries rank by how many words a post matches (best first),
    // and a date like "5 July" also pulls in posts made that day (in any year).
    $words = array_slice(preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY), 0, 10);
    $date  = parse_search_date($search);

    $match = [];   // OR-ed WHERE conditions (a post matches if ANY holds)
    $score = [];   // relevance terms, summed (higher = better match)
    $whereP = [];
    $scoreP = [];
    foreach ($words as $w) {
        $like = '%' . addcslashes($w, '%_\\') . '%';       // escape the user's own %/_ wildcards
        $cond = "(title LIKE ? ESCAPE '\\' OR body LIKE ? ESCAPE '\\')";
        $match[]  = $cond;                          $whereP[] = $like; $whereP[] = $like;
        $score[]  = "CASE WHEN $cond THEN 1 ELSE 0 END";  $scoreP[] = $like; $scoreP[] = $like;
    }
    if ($date !== null) {
        $dcond = isset($date['ymd']) ? "date(created_at) = ?" : "strftime('%m-%d', created_at) = ?";
        $dval  = $date['ymd'] ?? $date['md'];
        $match[] = $dcond;                          $whereP[] = $dval;
        $score[] = "CASE WHEN $dcond THEN 100 ELSE 0 END"; $scoreP[] = $dval;   // date hits rank on top
    }

    if ($match) {
        $sql = 'SELECT * FROM questions WHERE (' . implode(' OR ', $match) . ')'
             . ' ORDER BY (' . implode(' + ', $score) . ') DESC, created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($whereP, $scoreP));
        $questions = $stmt->fetchAll();
    } else {
        $questions = [];
    }

    // Log the search — anonymous: the query, how many posts it matched, and when.
    // Only when it was actually submitted from our own site (same-origin Referer), so a
    // cross-site "<img src=…?q=…>" can't force junk rows into the report. Best-effort.
    // HTTP_HOST may include a port, so compare host and host:port both ways.
    $reqHost     = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $ref         = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''));
    $refHost     = $ref['host'] ?? null;
    $refHostPort = $refHost === null ? null : $refHost . (isset($ref['port']) ? ':' . $ref['port'] : '');
    if ($reqHost !== '' && ($refHost === $reqHost || $refHostPort === $reqHost)) {
        try {
            $pdo->prepare('INSERT INTO searches (query, result_count) VALUES (?, ?)')
                ->execute([mb_substr($search, 0, 200), count($questions)]);
        } catch (\PDOException $e) {
        }
    }

    // No matches → don't dead-end: show the all-time most-engaging morning instead.
    // (The logged result_count above stays 0 — this fallback isn't a real match.)
    if (!$questions) {
        $searchNoResults = true;
        $questions = $pdo->query(
            'SELECT * FROM questions ORDER BY comment_count DESC, created_at DESC LIMIT 1'
        )->fetchAll();
    }
} else {
    // Home shows only the last 7 days; anything older lives in the archive.
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE created_at >= datetime('now', '-7 days') ORDER BY $orderBy");
    $stmt->execute();
    $questions = $stmt->fetchAll();
    // Never leave home blank: if nothing was posted this past week, show the latest one.
    if (!$questions) {
        $questions = $pdo->query('SELECT * FROM questions ORDER BY created_at DESC LIMIT 1')->fetchAll();
    }
}

// reusable query: the single most-discussed approved comment for a question
$topStmt = $pdo->prepare(
    'SELECT * FROM comments
     WHERE question_id = ? AND approved = 1 AND is_admin_reply = 0
     ORDER BY reply_count DESC, created_at ASC
     LIMIT 1'
);

$pageTitle = SITE_NAME;
$navActive = 'home';
require __DIR__ . '/../app/views/header.php';
?>
<div class="app">
  <div class="shell">

    <nav class="left">
      <?php include __DIR__ . '/../app/views/leftnav.php'; ?>
    </nav>

    <main class="center">
      <div class="feedhead">
        <b><?= $search !== '' ? 'Search · “' . e($search) . '”' : e($sortLabels[$sort] ?? 'Latest') ?></b>
      </div>

      <?php if (!$questions): ?>
        <div class="empty">
          <?= $search !== ''
              ? 'Nothing matches “' . e($search) . '”. Try different words, or a date like “5 July”.'
              : 'No mornings yet. Bob’s first question arrives with tomorrow’s coffee.' ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($searchNoResults) && $questions): ?>
        <div class="search-note">No matches for “<?= e($search) ?>” — here’s the morning people are talking about.</div>
      <?php endif; ?>

      <?php foreach ($questions as $q):
          $topStmt->execute([$q['id']]);
          $top = $topStmt->fetch();
          include __DIR__ . '/../app/views/post-card.php';
      endforeach; ?>
    </main>

    <aside class="right">
      <?php include __DIR__ . '/../app/views/sidebar.php'; ?>
    </aside>

  </div>
</div>
<?php require __DIR__ . '/../app/views/footer.php'; ?>
