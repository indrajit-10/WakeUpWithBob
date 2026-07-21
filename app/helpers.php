<?php
/** Small shared helpers used across pages. */

/** Escape text before printing it into HTML (prevents XSS / broken markup). */
function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/** The 123 Greetings logo image, used everywhere the brand appears. $variant sizes it (''|'lg'|'md'). */
function g123_logo(string $variant = ''): string {
    $cls = 'g123-logo' . ($variant !== '' ? ' g123-' . $variant : '');
    return '<img class="' . e($cls) . '" src="' . e(G123_LOGO) . '" alt="123Greetings" loading="lazy">';
}

/** Send the browser to another URL and stop. */
function redirect(string $to): void {
    header('Location: ' . $to);
    exit;
}

/** True when the current request arrived over HTTPS (so cookies can be marked Secure). */
function is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (($_SERVER['SERVER_PORT'] ?? null) == 443) {
        return true;
    }
    // behind a reverse proxy / load balancer that terminates TLS
    return isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
        && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
}

/** Start the PHP session once, with hardened cookie flags. Safe to call anywhere (no-op if already started). */
function ensure_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // HttpOnly stops JS from reading the session id; SameSite=Lax blunts CSRF;
        // Secure is set automatically once the site is served over HTTPS.
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => is_https(),
        ]);
        session_start();
    }
}

/** The CSRF token for this visitor's session (created on first use). */
function csrf_token(): string {
    ensure_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** A hidden field carrying the token. Put this inside every <form method="post">. */
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Bounce anyone who reaches a POST-only handler with a plain GET (typed URL, bookmark, bot) back home. */
function require_post(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        redirect('/');
    }
}

/** Reject the request unless it carried a matching token. Call at the top of every POST handler. */
function csrf_check(): void {
    ensure_session();
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(400);
        exit('Sorry — the security check failed (your session may have expired). Please go back, refresh the page, and try again.');
    }
}

/** Return a cookie-based voter token for likes. */
function get_voter_token(): string {
    if (!empty($_COOKIE['voter_token']) && is_string($_COOKIE['voter_token']) && preg_match('/^[a-f0-9]{64}$/', $_COOKIE['voter_token'])) {
        return $_COOKIE['voter_token'];
    }

    $token = bin2hex(random_bytes(32));
    setcookie('voter_token', $token, [
        'expires' => time() + 31536000,
        'path' => '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE['voter_token'] = $token;
    return $token;
}

/** Store a one-shot message that survives a single redirect (e.g. "Thanks — sent to Bob"). */
function flash_set(string $message): void {
    ensure_session();
    $_SESSION['flash'] = $message;
}

/** Read and clear the one-shot flash message (or null if there isn't one). */
function flash_get(): ?string {
    ensure_session();
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

/** Remember the commenter's name for this browser session so we can prefill it next time. */
function remember_name(string $name): void {
    $name = trim($name);
    if ($name !== '') {
        ensure_session();
        $_SESSION['reader_name'] = $name;
    }
}

/** The name this browser last commented with (empty string if none yet). */
function remembered_name(): string {
    ensure_session();
    return $_SESSION['reader_name'] ?? '';
}

/** Trim and hard-cap free text so a single submission can't bloat the database.
 *  Non-string input (e.g. an array-shaped POST field) is treated as empty rather than crashing. */
function clip(mixed $text, int $max): string {
    if (!is_string($text)) {
        return '';
    }
    $text = trim($text);
    return mb_strlen($text) > $max ? mb_substr($text, 0, $max) : $text;
}

/** Safely read an integer from $_POST — returns 0 for missing or non-scalar (array-shaped) input. */
function post_int(string $key): int {
    $v = $_POST[$key] ?? 0;
    return is_scalar($v) ? (int) $v : 0;
}

/** Return the URL only if it is a well-formed http(s) link, else empty string (blocks javascript:, data:, CSS breakouts). */
function clean_http_url(string $url): string {
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    return (preg_match('#^https?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL)) ? $url : '';
}

/** Friendly "3 hr. ago" style timestamp from a stored datetime string. */
function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60)   . ' min. ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' hr. ago';
    if ($diff < 172800) return '1 day ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j', strtotime($datetime));
}
