<?php
/**
 * The feed  (  /  )  — the first real page.
 * Reads questions from SQLite and renders a card for each one.
 */
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

ensure_session(); // start the session before any output so csrf_field() can set its cookie

// which questions has THIS browser already liked? (to show the filled/red state)
$voterToken = get_voter_token();
$likedStmt = $pdo->prepare('SELECT target_id FROM likes WHERE target_type = ? AND voter_token = ?');
$likedStmt->execute(['question', $voterToken]);
$likedQuestions = array_map('intval', $likedStmt->fetchAll(PDO::FETCH_COLUMN));

// --- read the sort + search from the URL ($_GET) ---
$sort   = $_GET['sort'] ?? 'latest';
$search = trim($_GET['q'] ?? '');

$sortLabels = ['latest' => 'Latest', 'oldest' => 'Oldest', 'popular' => 'Most popular', 'engaging' => 'Most engaging'];

$orderBy = match ($sort) {
    'oldest'   => 'created_at ASC',
    'popular'  => 'like_count DESC',
    'engaging' => '(like_count + comment_count * 2) DESC',   // likes + comments×2
    default    => 'created_at DESC',                          // latest
};

// --- fetch the questions (title-only search when a query is present) ---
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE title LIKE ? ORDER BY $orderBy");
    $stmt->execute(['%' . $search . '%']);
    $questions = $stmt->fetchAll();
} else {
    $questions = $pdo->query("SELECT * FROM questions ORDER BY $orderBy")->fetchAll();
}

// reusable query: the single most-engaging approved comment for a question
$topStmt = $pdo->prepare(
    'SELECT * FROM comments
     WHERE question_id = ? AND approved = 1 AND is_admin_reply = 0
     ORDER BY (like_count + reply_count * 2) DESC, created_at ASC
     LIMIT 1'
);

$pageTitle = SITE_NAME;
require __DIR__ . '/../app/views/header.php';
?>
<div class="app">
  <div class="shell">

    <nav class="left">
      <a class="nav active" href="/"><svg class="ico"><use href="#i-home"/></svg>Home</a>
      <a class="nav" href="/archive.php"><svg class="ico"><use href="#i-clock"/></svg>Archive</a>
      <div class="rail-sep"></div>
      <div class="rail-label">Sort the feed</div>
      <?php foreach ($sortLabels as $key => $label): ?>
        <a class="nav sub <?= $sort === $key ? 'active' : '' ?>"
           href="/?sort=<?= $key ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
      <div class="rail-sep"></div>
      <div class="rail-label">Community</div>
      <a class="nav" href="/about.php"><svg class="ico"><use href="#i-info"/></svg>About Bob</a>
    </nav>

    <main class="center">
      <div class="feedhead">
        <b><?= e($sortLabels[$sort] ?? 'Latest') ?><?= $search !== '' ? ' · “' . e($search) . '”' : '' ?></b>
      </div>

      <?php if (!$questions): ?>
        <div class="empty">
          <?= $search !== ''
              ? 'Nothing matches “' . e($search) . '”. Try a shorter title.'
              : 'No mornings yet. Bob’s first question arrives with tomorrow’s coffee.' ?>
        </div>
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
