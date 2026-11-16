<?php
/**
 * Receives a sign-up from the "Subscribe To Read More" popup or the sidebar box
 * (  POST /subscribe.php  ) and sends the reader back to the page they were on,
 * where the top banner shows the result.
 *
 * The address is stored here (Admin → Subscribers) and also added to the
 * 123Greetings newsletter list, the same list the form on newsletter.123greetings.com
 * fills (see newsletter_add() below). No email is sent from here.
 *
 * Signing up twice with the same address keeps the first row and shows the same
 * thank-you, so this form cannot be used to find out who is subscribed.
 */
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

/** Where the 123Greetings newsletter list takes sign-ups (POST email=…, answers {"ok":…,"msg":…}). */
const NEWSLETTER_SUBSCRIBE_URL = 'https://secondchance.123greetings.info/newsletter/subscribe';

/**
 * Adds one address to the 123Greetings newsletter list. Sent from this server, so
 * the reader's browser never talks to that site. It never stops a sign-up: if that
 * server is slow, down or turns the address away, the reader waits at most about
 * 5 seconds, still gets the thank-you and is still in Admin → Subscribers, and the
 * reason is written to the PHP error log.
 */
function newsletter_add(string $email): void
{
    $body    = http_build_query(['email' => $email]);
    $headers = [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (compatible; human.123greetings.com sign-up)',
    ];
    $status = 0;
    $error  = '';

    // Some hosts keep curl but switch off curl_exec: then use the other way.
    if (function_exists('curl_init') && function_exists('curl_exec')) {
        $ch = curl_init(NEWSLETTER_SUBSCRIBE_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT        => 5,
        ]);
        $reply  = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error  = curl_error($ch);
    } else {
        // No curl on this server: PHP's own HTTP client (needs allow_url_fopen on).
        $reply = @file_get_contents(NEWSLETTER_SUBSCRIBE_URL, false, stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => implode("\r\n", $headers),
            'content'       => $body,
            'timeout'       => 5,
            'ignore_errors' => true,              // read their answer even on an error status
        ]]));
        if ($reply === false) {
            $error = (string) (error_get_last()['message'] ?? 'no answer');
        }
    }

    $answer = is_string($reply) ? json_decode($reply, true) : null;
    if (is_array($answer) && ($answer['ok'] ?? null) === true) {
        return;                                   // added to their list
    }
    $why = (is_array($answer) && is_string($answer['msg'] ?? null)) ? $answer['msg']
         : ($error !== '' ? $error : 'unexpected answer');
    // The address itself is left out: readers' emails don't belong in a log file.
    // Their reply can repeat it ("… is already subscribed"), so it is masked there too.
    $why = str_ireplace($email, '[address]', $why);
    error_log('subscribe: newsletter list did not take an address'
        . ($status > 0 ? ' (HTTP ' . $status . ')' : '') . ': '
        . preg_replace('/[\x00-\x1F\x7F]+/', ' ', mb_substr($why, 0, 200)));
}

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

// Saved here; now the same address goes onto the 123Greetings newsletter list too.
// Someone already in Admin → Subscribers who signs up again is sent again: that
// list deals with repeats itself. Nothing that goes wrong in there may cost the
// reader their thank-you.
try {
    newsletter_add($email);
} catch (Throwable $e) {
    error_log('subscribe: newsletter list call failed: ' . get_class($e) . ': ' . $e->getMessage());
}

flash_set($thanks);
redirect($back);
