<?php
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

require_post();
csrf_check();

$questionId = isset($_POST['question_id']) ? (int) $_POST['question_id'] : 0;
$parentId = isset($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
$authorName = clip($_POST['author_name'] ?? '', 80);
$body = clip($_POST['comment_body'] ?? '', 5000);

if ($questionId <= 0 || $body === '') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

// the question must actually exist (otherwise the FK would 500 on insert)
$qexists = $pdo->prepare('SELECT 1 FROM questions WHERE id = ? LIMIT 1');
$qexists->execute([$questionId]);
if (!$qexists->fetchColumn()) {
    redirect('/');
}

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

redirect('/question.php?id=' . $questionId . '#comment-' . $commentId);
