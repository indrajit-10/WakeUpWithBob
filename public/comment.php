<?php
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

require_post();
csrf_check();

$questionId = post_int('question_id');
$parentId = post_int('parent_id') ?: null;
$authorName = clip($_POST['author_name'] ?? '', 80);
$body = clip($_POST['comment_body'] ?? '', 5000);

if ($questionId <= 0 || $body === '') {
    redirect(safe_local_redirect($_SERVER['HTTP_REFERER'] ?? null));
}

// the question must actually exist (otherwise the FK would 500 on insert)
$qrow = $pdo->prepare('SELECT title, body, post_number FROM questions WHERE id = ? AND ' . published_sql() . ' LIMIT 1');
$qrow->execute([$questionId]);
$qpost = $qrow->fetch();
if (!$qpost) {
    redirect('/');
}
// titles are optional, so fall back to an excerpt / post number for the alert subject
$qtitle = post_label($qpost['title'], $qpost['body'], $qpost['post_number'] ?? null);

// if this is a reply, the parent must exist AND belong to the same question (no cross-thread replies)
if ($parentId && $parentId > 0) {
    $pcheck = $pdo->prepare('SELECT 1 FROM comments WHERE id = ? AND question_id = ? LIMIT 1');
    $pcheck->execute([$parentId, $questionId]);
    if (!$pcheck->fetchColumn()) {
        $parentId = null;   // ignore a bad or cross-thread parent — treat as a top-level comment
    }
}

$stmt = $pdo->prepare('INSERT INTO comments (question_id, parent_id, author_name, body) VALUES (?, ?, ?, ?)');
$stmt->execute([
    $questionId,
    $parentId > 0 ? $parentId : null,
    $authorName !== '' ? $authorName : null,
    $body,
]);
$commentId = (int)$pdo->lastInsertId();
remember_name($authorName);                     // prefill their name next time
$_SESSION['just_posted_comment'] = $commentId;  // show a translucent "pending" preview once, to this browser

// Best-effort: ping the admin that a comment is waiting for review.
$who  = $authorName !== '' ? $authorName : 'A reader';
$link = (defined('SITE_URL') ? SITE_URL : '') . '/admin/comments.php';
send_admin_alert(
    'New comment on “' . $qtitle . '”',
    '<p><strong>' . e($who) . '</strong> commented on “' . e((string) $qtitle) . '”:</p>'
    . '<blockquote>' . nl2br(e($body)) . '</blockquote>'
    . '<p><a href="' . e($link) . '">Review it in moderation →</a></p>',
    "$who commented on \"$qtitle\":\n\n$body\n\nReview: $link"
);

redirect('/question.php?id=' . $questionId . '#comment-' . $commentId);
