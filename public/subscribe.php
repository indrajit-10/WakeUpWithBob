<?php
/**
 * Receives a sign-up from the "Subscribe To Read More" popup or the sidebar box
 * (  POST /subscribe.php  ) and sends the reader back to the page they were on,
 * where the top banner shows the result.
 *
 * Phase 1: the address is only stored. No email is sent from here — Bob downloads
 * the list from Admin → Subscribers and hands it to the mailing team.
 *
 * Signing up twice with the same address keeps the first row and shows the same
 * thank-you, so this form cannot be used to find out who is subscribed.
 */
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

require_post();
ensure_session();

$back   = safe_local_redirect($_SERVER['HTTP_REFERER'] ?? null);
$thanks = 'Thanks! You’re subscribed. Bob’s posts will start arriving in your inbox.';

// Not csrf_check(): that ends on a bare error page, and a reader who left a post
// open overnight should get a friendly "try again" on the page they were reading.
$sent = $_POST['csrf'] ?? '';
if (!is_string($sent) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
    flash_set('Sorry, that didn’t go through. Please try again.');
    redirect($back);
}

// Honeypot: a box people never see. Bots fill in every field — act as if it
// worked, so they have no reason to try again, and store nothing.
if (clip($_POST['website'] ?? '', 200) !== '') {
    flash_set($thanks);
    redirect($back);
}

// At most 5 sign-ups per visitor session in any 10 minutes.
$now    = time();
$recent = array_values(array_filter(
    is_array($_SESSION['subscribe_times'] ?? null) ? $_SESSION['subscribe_times'] : [],
    static fn ($t): bool => is_int($t) && $t > $now - 600
));
if (count($recent) >= 5) {
    flash_set('Too many tries. Please wait a few minutes and try again.');
    redirect($back);
}

// Rejected rather than trimmed to fit: cutting a long address short could store
// somebody else's. A leading = + - @ is refused too — legal in an address, but
// Excel would run it as a formula when the list is opened. And the part after
// the @ must be an ordinary domain (x@[127.0.0.1] passes PHP's check, but no
// mailing tool wants it).
$email = $_POST['email'] ?? '';
$email = is_string($email) ? strtolower(trim($email)) : '';
if ($email === '' || strlen($email) > 254
    || filter_var($email, FILTER_VALIDATE_EMAIL) === false
    || preg_match('/^[=+\-@]/', $email)
    || !preg_match('/@[a-z0-9.-]+\.[a-z0-9-]{2,}$/', $email)) {
    flash_set('Please enter a valid email address.');
    redirect($back);
}

$recent[] = $now;
$_SESSION['subscribe_times'] = $recent;

// Where they signed up. Only a real, published post id is kept; anything else is 0.
$source = (($_POST['source'] ?? '') === 'sidebar') ? 'sidebar' : 'post';
$qid    = 0;
if ($source === 'post' && post_int('q') > 0) {
    $stmt = $pdo->prepare('SELECT id FROM questions WHERE id = ? AND ' . published_sql() . ' LIMIT 1');
    $stmt->execute([post_int('q')]);
    $qid = (int) $stmt->fetchColumn();
}

// If the save fails (database busy, or the table not created yet), the reader
// gets "try again" on their own page instead of the bare error screen.
try {
    $pdo->prepare('INSERT OR IGNORE INTO subscribers (email, source, question_id) VALUES (?, ?, ?)')
        ->execute([$email, $source, $qid]);
} catch (PDOException $e) {
    error_log('subscribe: ' . $e->getMessage());
    flash_set('Sorry, that didn’t go through. Please try again.');
    redirect($back);
}

flash_set($thanks);
redirect($back);
