<?php
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

require_post();
csrf_check();

$commentId = isset($_POST['comment_id']) ? (int) $_POST['comment_id'] : 0;
$back = $_SERVER['HTTP_REFERER'] ?? '/';
if ($commentId <= 0) {
    redirect($back);
}

// the target must exist — otherwise we'd leave an orphan row in `likes`
$exists = $pdo->prepare('SELECT 1 FROM comments WHERE id = ? LIMIT 1');
$exists->execute([$commentId]);
if (!$exists->fetchColumn()) {
    redirect($back);
}

$voterToken = get_voter_token();

// toggle: like if not liked yet, otherwise unlike
$stmt = $pdo->prepare('SELECT id FROM likes WHERE target_type = ? AND target_id = ? AND voter_token = ?');
$stmt->execute(['comment', $commentId, $voterToken]);
$existing = $stmt->fetchColumn();

if ($existing) {
    $pdo->prepare('DELETE FROM likes WHERE id = ?')->execute([$existing]);
    $pdo->prepare('UPDATE comments SET like_count = MAX(like_count - 1, 0) WHERE id = ?')->execute([$commentId]);
} else {
    $pdo->prepare('INSERT INTO likes (target_type, target_id, voter_token) VALUES (?, ?, ?)')->execute(['comment', $commentId, $voterToken]);
    $pdo->prepare('UPDATE comments SET like_count = like_count + 1 WHERE id = ?')->execute([$commentId]);
}

// return to the comment on the question page if we can resolve it
$c = $pdo->prepare('SELECT question_id FROM comments WHERE id = ? LIMIT 1');
$c->execute([$commentId]);
$row = $c->fetch();
if ($row) {
    redirect('/question.php?id=' . (int) $row['question_id'] . '#comment-' . $commentId);
}
redirect($back);
