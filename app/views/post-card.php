<?php
/**
 * One post card in the feed.
 * Expects:  $q   (a question row)   and   $top (its top comment row, or false).
 */
$qid = (int) $q['id'];
// Set by the feed when a search is active; empty everywhere else, so ordinary
// browsing renders exactly as before.
$hl = $highlightTerms ?? [];
?>
<article class="post">
  <div class="post-meta">
    <span class="avatar"><img class="logo" src="/assets/img/logo.svg" alt="" width="19" height="19"></span>
    <a class="community" href="/question.php?id=<?= $qid ?>">Wake up with Bob</a>
    <span>·</span>
    <span class="time"><?= e(date('F j, Y', db_time($q['created_at']))) ?></span>
  </div>

  <?php if (trim((string) $q['title']) !== ''): ?>
    <h2 class="post-title"><a href="/question.php?id=<?= $qid ?>"><?= e_highlight($q['title'], $hl) ?></a></h2>
  <?php endif; ?>
  <p class="post-text post-body"><?= format_post_text($q['body'], $hl) ?></p>

  <?php if (!empty($q['image_url'])): ?>
    <div class="post-img" style="background-image:url('<?= e(css_url_value($q['image_url'])) ?>')"></div>
  <?php endif; ?>

  <div class="actions">
    <button class="pill" type="button" data-scrollto="comment-form-<?= $qid ?>">
      <svg class="ico ico-sm"><use href="#i-comment"/></svg><?= (int) $q['comment_count'] ?>
    </button>
    <button class="pill" type="button"><svg class="ico ico-sm"><use href="#i-share"/></svg>Share</button>
  </div>

  <?php if ($top): ?>
    <div class="top-comment">
      <div class="tc-inset">
        <div class="tc-meta"><b><?= e($top['author_name'] ?: 'a reader') ?></b> · <?= e(time_ago($top['created_at'])) ?></div>
        <div class="tc-text"><?= e($top['body']) ?></div>
        <div class="tc-actions">
          <span><svg class="ico"><use href="#i-comment"/></svg> <?= (int) $top['reply_count'] ?> replies</span>
        </div>
      </div>
      <a class="tc-more" href="/question.php?id=<?= $qid ?>">View all <?= (int) $q['comment_count'] ?> comments &rarr;</a>
    </div>
  <?php else: ?>
    <div class="top-comment">
      <span class="tc-more">No public comments yet.</span>
    </div>
  <?php endif; ?>

  <div class="inline-comment-section" id="comment-form-<?= $qid ?>">
    <form class="inline-comment-form" method="post" action="/comment.php">
      <?= csrf_field() ?>
      <input type="hidden" name="question_id" value="<?= $qid ?>">
      <input type="text" name="author_name" placeholder="Name (optional)" class="inline-input" value="<?= e(remembered_name()) ?>">
      <textarea name="comment_body" rows="3" placeholder="Write a comment…" class="inline-textarea"></textarea>
      <button class="btn-orange" type="submit">Submit comment</button>
    </form>
  </div>
</article>
