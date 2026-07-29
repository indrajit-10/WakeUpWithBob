<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

// How long an admin may stay signed in without any activity before we ask for
// the password again. Default 30 minutes; override with the ADMIN_IDLE_TIMEOUT
// env var (in seconds). Guards against a 0/negative value falling through.
if (!defined('ADMIN_IDLE_TIMEOUT')) {
    $__t = (int) (getenv('ADMIN_IDLE_TIMEOUT') ?: 1800);
    define('ADMIN_IDLE_TIMEOUT', $__t > 0 ? $__t : 1800);
}

ensure_session();   // hardened session cookie flags (HttpOnly, SameSite, Secure-on-HTTPS)
admin_enforce_idle();   // sign out an admin who's been idle too long

/** Auto sign-out an idle admin: if it's been longer than ADMIN_IDLE_TIMEOUT
 *  since the last request, drop the admin keys so the next page shows the
 *  sign-in screen again. Otherwise slide the window forward. */
function admin_enforce_idle(?int $now = null): void {
    if (empty($_SESSION['admin_id'])) {
        return;
    }
    $now  = $now ?? time();
    $last = (int) ($_SESSION['admin_last_seen'] ?? 0);
    if ($now - $last > ADMIN_IDLE_TIMEOUT) {
        unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_last_seen']);
    } else {
        $_SESSION['admin_last_seen'] = $now;
    }
}

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
    $_SESSION['admin_last_seen'] = time();
    return true;
}

function admin_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}

function admin_logout(): void {
    unset($_SESSION['admin_id'], $_SESSION['admin_username']);
    session_destroy();
}

/** Number every post by date, oldest = #1. Called after any insert/edit that can
 *  change chronology (backdating, scheduling), so the archive always reads 1,2,3…
 *  in time order instead of in the order Bob happened to type them in. */
function resequence_post_numbers(): void {
    global $pdo;
    $ids = $pdo->query('SELECT id FROM questions ORDER BY created_at ASC, id ASC')->fetchAll(PDO::FETCH_COLUMN);
    $upd = $pdo->prepare('UPDATE questions SET post_number = ? WHERE id = ?');

    // ONE transaction for the whole renumber. Without it each UPDATE autocommits
    // (an fsync apiece): ~3.5 s at 1000 posts and ~19 s at 5000, on every publish,
    // date-edit and delete — versus ~15 ms batched. It also makes the renumber
    // atomic, so readers never catch two posts sharing a number, and a mid-loop
    // failure rolls back instead of leaving the sequence permanently duplicated.
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $n = 1;
        foreach ($ids as $qid) {
            $upd->execute([$n++, $qid]);
        }
        if ($owns) {
            $pdo->commit();
        }
    } catch (\Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/** Create a post. $publishAt is a UTC 'Y-m-d H:i:s' string: in the past to backdate
 *  it into the archive, in the future to schedule it. Null means "right now". */
function create_question(string $title, string $body, ?string $imageUrl = null, ?string $publishAt = null): int {
    global $pdo;

    if ($publishAt !== null && $publishAt !== '') {
        $stmt = $pdo->prepare('INSERT INTO questions (title, body, image_url, post_number, created_at) VALUES (?, ?, ?, 0, ?)');
        $stmt->execute([$title, $body, $imageUrl, $publishAt]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO questions (title, body, image_url, post_number) VALUES (?, ?, ?, 0)');
        $stmt->execute([$title, $body, $imageUrl]);
    }
    $newId = (int) $pdo->lastInsertId();
    resequence_post_numbers();   // keep #1 = the oldest morning, even when backfilling
    return $newId;
}

/** Fetch a single question (used to pre-fill the edit form). */
function get_question(int $questionId): ?array {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM questions WHERE id = ? LIMIT 1');
    $stmt->execute([$questionId]);
    $q = $stmt->fetch();
    return $q ?: null;
}

/** Edit an existing question's title / body / image, and optionally move its publish
 *  date ($publishAt = UTC 'Y-m-d H:i:s'; null leaves the existing date alone). */
function update_question(int $questionId, string $title, string $body, ?string $imageUrl = null, ?string $publishAt = null): void {
    global $pdo;
    if ($publishAt !== null && $publishAt !== '') {
        $stmt = $pdo->prepare('UPDATE questions SET title = ?, body = ?, image_url = ?, created_at = ? WHERE id = ?');
        $stmt->execute([$title, $body, $imageUrl, $publishAt, $questionId]);
        resequence_post_numbers();   // the date moved, so chronology may have changed
        return;
    }
    $stmt = $pdo->prepare('UPDATE questions SET title = ?, body = ?, image_url = ? WHERE id = ?');
    $stmt->execute([$title, $body, $imageUrl, $questionId]);
}

function delete_question(int $questionId): void {
    global $pdo;

    $stmt = $pdo->prepare('DELETE FROM questions WHERE id = ?');
    $stmt->execute([$questionId]);
    // Close the gap the deletion leaves, so numbering stays a contiguous 1..N in
    // date order (create/edit already resequence — without this, deleting #3 would
    // read 1,2,4,5 until the next post silently renumbered everything anyway).
    resequence_post_numbers();
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
