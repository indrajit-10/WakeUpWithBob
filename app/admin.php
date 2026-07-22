<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

ensure_session();   // hardened session cookie flags (HttpOnly, SameSite, Secure-on-HTTPS)

function admin_login(string $username, string $password): bool {
    global $pdo;

    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin) {
        return false;
    }

    if (!password_verify($password, $admin['password_hash'])) {
        return false;
    }

    // new session id on privilege change — defeats session fixation
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    return true;
}

function admin_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}

function admin_logout(): void {
    unset($_SESSION['admin_id'], $_SESSION['admin_username']);
    session_destroy();
}

function create_question(string $title, string $body, ?string $imageUrl = null): int {
    global $pdo;

    // permanent, monotonic post number: 1 for the first post ever, then +1 each time
    $next = (int) $pdo->query('SELECT COALESCE(MAX(post_number), 0) + 1 FROM questions')->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO questions (title, body, image_url, post_number) VALUES (?, ?, ?, ?)');
    $stmt->execute([$title, $body, $imageUrl, $next]);
    return (int) $pdo->lastInsertId();
}

/** Fetch a single question (used to pre-fill the edit form). */
function get_question(int $questionId): ?array {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM questions WHERE id = ? LIMIT 1');
    $stmt->execute([$questionId]);
    $q = $stmt->fetch();
    return $q ?: null;
}

/** Edit an existing question's title / body / image. */
function update_question(int $questionId, string $title, string $body, ?string $imageUrl = null): void {
    global $pdo;
    $stmt = $pdo->prepare('UPDATE questions SET title = ?, body = ?, image_url = ? WHERE id = ?');
    $stmt->execute([$title, $body, $imageUrl, $questionId]);
}

function delete_question(int $questionId): void {
    global $pdo;

    $stmt = $pdo->prepare('DELETE FROM questions WHERE id = ?');
    $stmt->execute([$questionId]);
}

function approve_comment(int $commentId): void {
    global $pdo;

    $stmt = $pdo->prepare('SELECT question_id, parent_id, approved FROM comments WHERE id = ? LIMIT 1');
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();
    if (!$comment || (int)$comment['approved'] === 1) {
        return;
    }

    $pdo->prepare('UPDATE comments SET approved = 1 WHERE id = ?')->execute([$commentId]);
    $pdo->prepare('UPDATE questions SET comment_count = comment_count + 1 WHERE id = ?')->execute([$comment['question_id']]);

    if ($comment['parent_id']) {
        $pdo->prepare('UPDATE comments SET reply_count = reply_count + 1 WHERE id = ?')->execute([$comment['parent_id']]);
    }
}

/** Approve every pending comment on one question at once ("Approve all on this post"). */
function approve_all_for_question(int $questionId): int {
    global $pdo;
    $ids = $pdo->prepare('SELECT id FROM comments WHERE question_id = ? AND approved = 0 ORDER BY created_at ASC');
    $ids->execute([$questionId]);
    $n = 0;
    foreach ($ids->fetchAll(PDO::FETCH_COLUMN) as $cid) {
        approve_comment((int) $cid);   // reuses the same counter/idempotency logic
        $n++;
    }
    return $n;
}

function reject_comment(int $commentId): void {
    global $pdo;

    // Note the post first: rejecting a comment can also cascade-delete approved child
    // replies, so we resync comment_count from the truth afterward (never leave it inflated).
    $stmt = $pdo->prepare('SELECT question_id FROM comments WHERE id = ? LIMIT 1');
    $stmt->execute([$commentId]);
    $questionId = $stmt->fetchColumn();

    $pdo->prepare('DELETE FROM comments WHERE id = ?')->execute([$commentId]);

    if ($questionId !== false) {
        $pdo->prepare(
            'UPDATE questions SET comment_count =
               (SELECT COUNT(*) FROM comments WHERE question_id = ? AND approved = 1)
             WHERE id = ?'
        )->execute([$questionId, $questionId]);
    }
}

function reply_to_comment(int $commentId, string $replyBody): void {
    global $pdo;

    $stmt = $pdo->prepare('SELECT question_id FROM comments WHERE id = ? LIMIT 1');
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();
    if (!$comment) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO comments (question_id, parent_id, author_name, body, is_admin_reply, approved) VALUES (?, ?, ?, ?, 1, 1)');
    $stmt->execute([$comment['question_id'], $commentId, 'Bob', $replyBody]);
    $pdo->prepare('UPDATE comments SET reply_count = reply_count + 1 WHERE id = ?')->execute([$commentId]);
    // Bob's reply is itself an approved comment — count it too, so the feed card's
    // comment_count matches the thread header (which counts every approved comment).
    $pdo->prepare('UPDATE questions SET comment_count = comment_count + 1 WHERE id = ?')->execute([$comment['question_id']]);
}

/** Mark a reader-submitted question as handled so it drops out of the "new" inbox. */
function dismiss_reader_question(int $id): void {
    global $pdo;
    $pdo->prepare("UPDATE reader_questions SET status = 'dismissed' WHERE id = ?")->execute([$id]);
}

/** Mark a feedback item as done. */
function mark_feedback_done(int $id): void {
    global $pdo;
    $pdo->prepare("UPDATE feedback SET status = 'done' WHERE id = ?")->execute([$id]);
}
