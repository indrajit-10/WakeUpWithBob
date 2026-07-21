<?php
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

require_post();
csrf_check();

$questionId = isset($_POST['question_id']) ? (int) $_POST['question_id'] : 0;
$back = $_SERVER['HTTP_REFERER'] ?? '/';
if ($questionId <= 0) {
    redirect($back);
}

// the target must exist — otherwise we'd leave an orphan row in `likes`
$exists = $pdo->prepare('SELECT 1 FROM questions WHERE id = ? LIMIT 1');
$exists->execute([$questionId]);
if (!$exists->fetchColumn()) {
    redirect($back);
}

$voterToken = get_voter_token();

// toggle: if this browser already liked it, remove the like; otherwise add it
$stmt = $pdo->prepare('SELECT id FROM likes WHERE target_type = ? AND target_id = ? AND voter_token = ?');
$stmt->execute(['question', $questionId, $voterToken]);
$existing = $stmt->fetchColumn();

if ($existing) {
    $pdo->prepare('DELETE FROM likes WHERE id = ?')->execute([$existing]);
    $pdo->prepare('UPDATE questions SET like_count = MAX(like_count - 1, 0) WHERE id = ?')->execute([$questionId]);
} else {
    $pdo->prepare('INSERT INTO likes (target_type, target_id, voter_token) VALUES (?, ?, ?)')->execute(['question', $questionId, $voterToken]);
    $pdo->prepare('UPDATE questions SET like_count = like_count + 1 WHERE id = ?')->execute([$questionId]);
}

redirect($back);
