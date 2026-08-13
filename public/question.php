<?php
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

ensure_session(); // start the session before any output so csrf_field() can set its cookie

$questionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($questionId <= 0) {
    http_response_code(404);
    exit('Question not found.');
}

// A scheduled post (publish date still in the future) must not be readable by URL —
// otherwise a guessed or shared id would leak it before its morning.
$stmt = $pdo->prepare('SELECT * FROM questions WHERE id = ? AND ' . published_sql() . ' LIMIT 1');
$stmt->execute([$questionId]);
$question = $stmt->fetch();
if (!$question) {
    http_response_code(404);
    exit('Question not found.');
}

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
function render_comment_card(array $comment, array $repliesByParent, int $depth = 0): void {
    $isBob     = (int) $comment['is_admin_reply'] === 1;
    $cid       = (int) $comment['id'];
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
        <div class="cmt-pending-note" role="status">
          <span class="dot"></span>
          <span class="cmt-pending-text">Waiting for Bob to read it — it'll appear here once he approves.</span>
          <button type="button" class="cmt-pending-close" data-dismiss-closest=".cmt-pending-note" aria-label="Dismiss this notice">&times;</button>
        </div>
      <?php else: ?>
        <div class="cmt-actions">
          <button class="cmt-btn" type="button" data-toggle="reply-form-<?= $cid ?>" data-toggle-class="visible"><svg class="ico"><use href="#i-comment"/></svg> Reply</button>
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
              <?php render_comment_card($reply, $repliesByParent, $depth + 1); ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <?php
}

$pageTitle = post_label($question['title'], $question['body'], $question['post_number'] ?? null) . ' · ' . SITE_NAME;
require __DIR__ . '/../app/views/header.php';
?>
<div class="app">
  <div class="shell">

    <nav class="left">
      <?php include __DIR__ . '/../app/views/leftnav.php'; ?>
    </nav>

    <main class="center">
      <a class="backlink d-none" href="/">
        <svg viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7v-4h6v-6h-6z" fill="currentColor"/></svg>
        All mornings
      </a>

      <!-- the question, presented like a lead post -->
      <article class="post post--lead">
        <div class="post-meta">
          <span class="avatar"><img class="logo" src="/assets/img/logo.svg" alt="" width="19" height="19"></span>
          <a class="community" href="/question.php?id=<?= $questionId ?>">Five minutes with Bob</a>
          <span>· Posted by</span> <b class="byline">Bob</b>
          <span>·</span> <span class="time"><?= e(date('F j, Y', db_time($question['created_at']))) ?></span>
        </div>

        <?php if (trim((string) $question['title']) !== ''): ?>
          <h1 class="post-title lead-title"><?= e($question['title']) ?></h1>
        <?php endif; ?>
        <p class="post-text post-body"><?= format_post_text($question['body']) ?></p>

        <?php if (!empty($question['image_url'])): ?>
          <div class="post-img" style="background-image:url('<?= e(css_url_value($question['image_url'])) ?>')"></div>
        <?php endif; ?>

        <div class="actions">
          <a class="pill" href="#add-comment"><svg class="ico ico-sm"><use href="#i-comment"/></svg><?= $commentTotal ?> Comments</a>
          <?php $q = $question; include __DIR__ . '/../app/views/share-menu.php'; ?>
        </div>
      </article>

      <!-- the thread -->
      <section class="thread">
        <div class="thread-head">
          <h2><?= $commentTotal ?> Comment<?= $commentTotal === 1 ? '' : 's' ?></h2>
        </div>

        <?php if ($topComments): ?>
          <?php foreach ($topComments as $comment): ?>
            <?php render_comment_card($comment, $repliesByParent); ?>
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
            <span class="tiny d-none"><span class="dot"></span>Under review. It will appear shortly.</span>
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
