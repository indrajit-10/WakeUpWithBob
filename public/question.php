<?php
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

ensure_session(); // start the session before any output so csrf_field() can set its cookie
$voterToken = get_voter_token();

$questionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($questionId <= 0) {
    http_response_code(404);
    exit('Question not found.');
}

$stmt = $pdo->prepare('SELECT * FROM questions WHERE id = ? LIMIT 1');
$stmt->execute([$questionId]);
$question = $stmt->fetch();
if (!$question) {
    http_response_code(404);
    exit('Question not found.');
}

// has THIS browser liked the question / which comments?
$ql = $pdo->prepare('SELECT 1 FROM likes WHERE target_type = ? AND target_id = ? AND voter_token = ? LIMIT 1');
$ql->execute(['question', $questionId, $voterToken]);
$questionLiked = (bool) $ql->fetchColumn();

$cl = $pdo->prepare('SELECT target_id FROM likes WHERE target_type = ? AND voter_token = ?');
$cl->execute(['comment', $voterToken]);
$likedComments = array_map('intval', $cl->fetchAll(PDO::FETCH_COLUMN));

// approved comments, grouped into a parent → children tree
$comments = $pdo->prepare('SELECT * FROM comments WHERE question_id = ? AND approved = 1 ORDER BY created_at ASC');
$comments->execute([$questionId]);
$allComments = $comments->fetchAll();
$commentTotal = count($allComments);           // the real number of comments shown (top-level + replies)

// index approved ids so we can spot replies whose parent isn't approved (orphans)
$approvedIds = [];
foreach ($allComments as $c) { $approvedIds[(int) $c['id']] = true; }

$topComments = [];
$repliesByParent = [];
foreach ($allComments as $comment) {
    $pid = (int) ($comment['parent_id'] ?? 0);
    if ($pid && isset($approvedIds[$pid])) {
        $repliesByParent[$pid][] = $comment;   // nested under an approved parent
    } else {
        $topComments[] = $comment;             // top-level — or an orphan whose parent isn't approved (still shown)
    }
}

// Show the visitor a translucent copy of the comment THEY just submitted (still pending).
// Only their own browser sees it (via the session), and only until Bob approves it.
$justPosted = $_SESSION['just_posted_comment'] ?? null;
unset($_SESSION['just_posted_comment']);
if ($justPosted) {
    $pstmt = $pdo->prepare('SELECT * FROM comments WHERE id = ? AND question_id = ? AND approved = 0 LIMIT 1');
    $pstmt->execute([(int) $justPosted, $questionId]);
    $preview = $pstmt->fetch();
    if ($preview) {
        $preview['_preview'] = true;
        $ppid = (int) ($preview['parent_id'] ?? 0);
        if ($ppid && isset($approvedIds[$ppid])) {
            $repliesByParent[$ppid][] = $preview;   // show it nested under the comment they replied to
        } else {
            $topComments[] = $preview;              // or at the end of the thread
        }
    }
}

/** Recursively render a comment and all of its nested replies. Any comment can be replied to. */
function render_comment_card(array $comment, array $repliesByParent, array $likedComments, int $depth = 0): void {
    $isBob     = (int) $comment['is_admin_reply'] === 1;
    $cid       = (int) $comment['id'];
    $liked     = in_array($cid, $likedComments, true);
    $isPreview = !empty($comment['_preview']);   // the visitor's own just-submitted, still-pending comment
    ?>
    <div class="cmt<?= $isBob ? ' cmt--bob' : '' ?><?= $isPreview ? ' cmt--pending' : '' ?>" id="comment-<?= $cid ?>">
      <div class="cmt-head">
        <span class="cmt-author"><?= e($comment['author_name'] ?: ($isBob ? 'Bob' : 'A reader')) ?></span>
        <?php if ($isBob): ?><span class="tag-bob">Bob</span><?php endif; ?>
        <span class="cmt-time">· <?= $isPreview ? 'just now' : e(time_ago($comment['created_at'])) ?></span>
      </div>
      <div class="cmt-body"><?= e($comment['body']) ?></div>

      <?php if ($isPreview): ?>
        <div class="cmt-pending-note"><span class="dot"></span>Waiting for Bob to read it — it'll appear here once he approves.</div>
      <?php else: ?>
        <div class="cmt-actions">
          <form method="post" action="/comment-like.php" class="cmt-form-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="comment_id" value="<?= $cid ?>">
            <button class="cmt-btn<?= $liked ? ' liked' : '' ?>" type="submit"><svg class="ico"><use href="#i-heart"/></svg> <?= (int) $comment['like_count'] ?></button>
          </form>
          <button class="cmt-btn" type="button" onclick="document.getElementById('reply-form-<?= $cid ?>').classList.toggle('visible');"><svg class="ico"><use href="#i-comment"/></svg> Reply</button>
        </div>

        <form id="reply-form-<?= $cid ?>" class="reply-form" method="post" action="/comment.php">
          <?= csrf_field() ?>
          <input type="hidden" name="question_id" value="<?= (int) $comment['question_id'] ?>">
          <input type="hidden" name="parent_id" value="<?= $cid ?>">
          <input class="reply-name" type="text" name="author_name" placeholder="Your name (optional)" value="<?= e(remembered_name()) ?>">
          <textarea name="comment_body" rows="2" placeholder="Reply to <?= e($comment['author_name'] ?: ($isBob ? 'Bob' : 'this reader')) ?>…" required></textarea>
          <button class="btn-orange" type="submit">Submit reply</button>
        </form>

        <?php if (!empty($repliesByParent[$cid])): ?>
          <div class="cmt-replies">
            <?php foreach ($repliesByParent[$cid] as $reply): ?>
              <?php render_comment_card($reply, $repliesByParent, $likedComments, $depth + 1); ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <?php
}

$pageTitle = $question['title'] . ' · ' . SITE_NAME;
require __DIR__ . '/../app/views/header.php';
?>
<div class="app">
  <div class="shell">

    <nav class="left">
      <a class="nav" href="/"><svg class="ico"><use href="#i-home"/></svg>Home</a>
      <a class="nav" href="/archive.php"><svg class="ico"><use href="#i-clock"/></svg>Archive</a>
      <div class="rail-sep"></div>
      <div class="rail-label">Community</div>
      <a class="nav" href="/about.php"><svg class="ico"><use href="#i-info"/></svg>About Bob</a>
    </nav>

    <main class="center">
      <a class="backlink" href="/">
        <svg viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7v-4h6v-6h-6z" fill="currentColor"/></svg>
        All mornings
      </a>

      <!-- the question, presented like a lead post -->
      <article class="post post--lead">
        <div class="post-meta">
          <span class="avatar"><img class="logo" src="/assets/img/logo.svg" alt="" width="19" height="19"></span>
          <a class="community" href="/question.php?id=<?= $questionId ?>">Wake up with Bob</a>
          <span>· Posted by</span> <b class="byline">Bob</b>
          <span>·</span> <span class="time"><?= e(time_ago($question['created_at'])) ?></span>
        </div>

        <h1 class="post-title lead-title"><?= e($question['title']) ?></h1>
        <p class="post-text"><?= e($question['body']) ?></p>

        <?php if (!empty($question['image_url'])): ?>
          <div class="post-img" style="background-image:url('<?= e($question['image_url']) ?>')"></div>
        <?php endif; ?>

        <div class="actions">
          <form method="post" action="/like.php" class="cmt-form-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="question_id" value="<?= $questionId ?>">
            <button class="pill like<?= $questionLiked ? ' liked' : '' ?>" type="submit"><svg class="ico ico-sm"><use href="#i-heart"/></svg><span class="num"><?= (int) $question['like_count'] ?></span></button>
          </form>
          <a class="pill" href="#add-comment"><svg class="ico ico-sm"><use href="#i-comment"/></svg><?= $commentTotal ?> Comments</a>
          <button class="pill" type="button"><svg class="ico ico-sm"><use href="#i-share"/></svg>Share</button>
        </div>
      </article>

      <!-- the thread -->
      <section class="thread">
        <div class="thread-head">
          <h2><?= $commentTotal ?> Comment<?= $commentTotal === 1 ? '' : 's' ?></h2>
          <span class="approval-note">Read by Bob before they appear</span>
        </div>

        <?php if ($topComments): ?>
          <?php foreach ($topComments as $comment): ?>
            <?php render_comment_card($comment, $repliesByParent, $likedComments); ?>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="qempty">No replies yet — be the first to share your morning.</div>
        <?php endif; ?>
      </section>

      <!-- add a comment -->
      <section class="add-comment" id="add-comment">
        <h2>Join the conversation</h2>
        <form method="post" action="/comment.php">
          <?= csrf_field() ?>
          <input type="hidden" name="question_id" value="<?= $questionId ?>">
          <input class="field" type="text" name="author_name" placeholder="Your name (optional)" value="<?= e(remembered_name()) ?>">
          <textarea class="field" name="comment_body" rows="4" placeholder="Share your morning…" required></textarea>
          <div class="add-comment-bar">
            <span class="tiny"><span class="dot"></span>Held for Bob to approve before it appears.</span>
            <button class="btn-orange" type="submit">Post comment</button>
          </div>
        </form>
      </section>
    </main>

    <aside class="right">
      <?php include __DIR__ . '/../app/views/sidebar.php'; ?>
    </aside>

  </div>
</div>
<?php require __DIR__ . '/../app/views/footer.php'; ?>
