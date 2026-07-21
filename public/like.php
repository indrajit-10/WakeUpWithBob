<?php
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

require_post();
csrf_check();

$questionId = isset($_POST['question_id']) ? (int) $_POST['question_id'] : 0;
$back = safe_local_redirect($_SERVER['HTTP_REFERER'] ?? null);
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

// Toggle atomically. INSERT OR IGNORE is a no-op when this browser already liked it,
// so rowCount() tells us which way the toggle went — no read-then-write race, and the
// UNIQUE(target_type,target_id,voter_token) constraint can never throw on a double-tap.
// Wrapped in a transaction so the like row and the denormalised counter can't drift apart.
try {
    $pdo->beginTransaction();
    $ins = $pdo->prepare('INSERT OR IGNORE INTO likes (target_type, target_id, voter_token) VALUES (?, ?, ?)');
    $ins->execute(['question', $questionId, $voterToken]);
    if ($ins->rowCount() === 1) {
        $pdo->prepare('UPDATE questions SET like_count = like_count + 1 WHERE id = ?')->execute([$questionId]);
    } else {
        $pdo->prepare('DELETE FROM likes WHERE target_type = ? AND target_id = ? AND voter_token = ?')
            ->execute(['question', $questionId, $voterToken]);
        $pdo->prepare('UPDATE questions SET like_count = MAX(like_count - 1, 0) WHERE id = ?')->execute([$questionId]);
    }
    $pdo->commit();
} catch (\PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

redirect($back);
