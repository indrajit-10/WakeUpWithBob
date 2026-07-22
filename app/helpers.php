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

/** Turn a (client-supplied) Referer into a safe, same-site redirect target.
 *  Returns the Referer's local path when it points at this site, else the fallback —
 *  so a forged Referer can never bounce a visitor off to another origin. */
function safe_local_redirect(?string $referer, string $fallback = '/'): string {
    if (!is_string($referer) || $referer === '') {
        return $fallback;
    }
    $parts = parse_url($referer);
    if ($parts === false) {
        return $fallback;
    }
    if (isset($parts['host']) && $parts['host'] !== ($_SERVER['HTTP_HOST'] ?? '')) {
        return $fallback;   // points at another host — refuse it
    }
    $path = $parts['path'] ?? '/';
    // must be a plain local path — reject protocol-relative ("//") and "/\" tricks
    if ($path === '' || $path[0] !== '/' || (isset($path[1]) && ($path[1] === '/' || $path[1] === '\\'))) {
        return $fallback;
    }
    return $path
        . (isset($parts['query'])    ? '?' . $parts['query']    : '')
        . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
}

/** Send hardening HTTP headers on every web response (no-op on CLI or once output has begun). */
function send_security_headers(): void {
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }
    // This app ships NO inline scripts, so script-src can stay strict ('self') — a
    // strong brake on injected JavaScript. Inline styles are limited to a static SVG
    // and the background-image URLs, so style keeps 'unsafe-inline'. External images
    // are allowed over https (admin post images + the 123 Greetings logo).
    header("Content-Security-Policy: "
        . "default-src 'self'; "
        . "base-uri 'self'; "
        . "object-src 'none'; "
        . "frame-ancestors 'self'; "
        . "form-action 'self'; "
        . "img-src 'self' https: data:; "
        . "style-src 'self' 'unsafe-inline'; "
        . "script-src 'self'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
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

/** Store a one-shot message that survives a single redirect (e.g. "Thanks — sent to Bob"). */
function flash_set(string $message): void {
    ensure_session();
    $_SESSION['flash'] = $message;
}

/** Read and clear the one-shot flash message (or null if there isn't one).
 *  Cached per-request so it can be rendered in more than one place (e.g. the
 *  desktop rail AND the mobile drawer) without the first read clearing it. */
function flash_get(): ?string {
    static $cached = null;
    static $read = false;
    ensure_session();
    if (!$read) {
        $cached = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        $read = true;
    }
    return $cached;
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

/** Percent-encode the handful of characters that could break out of a CSS url('…')
 *  string, so a validated image URL is safe to drop into an inline style attribute.
 *  (FILTER_VALIDATE_URL still lets quotes/parens through, and the HTML parser decodes
 *  entities before the CSS parser sees them, so e() alone is not enough here.) */
function css_url_value(string $url): string {
    return strtr($url, [
        "'"  => '%27', '"'  => '%22', '('  => '%28', ')'  => '%29', '\\' => '%5C',
        ' '  => '%20', "\n" => '%0A', "\r" => '%0D', "\t" => '%09',
        '<'  => '%3C', '>'  => '%3E',
    ]);
}

/** Detect a date inside a search query, for date-aware search.
 *  Returns ['ymd' => 'YYYY-MM-DD'] when a full date (with a year) is given,
 *  ['md' => 'MM-DD'] for a day+month with no year (match that day in ANY year),
 *  or null when the query is not a recognisable date.
 *  Understands: '5 July', '5th July', 'July 5', 'July 5th' (optionally with a
 *  4-digit year) and ISO 'YYYY-MM-DD'. Bare months/numbers are NOT treated as
 *  dates, so ordinary keyword searches are never hijacked. */
function parse_search_date(string $q): ?array {
    $q = trim($q);
    if ($q === '') {
        return null;
    }
    $months = [
        'january' => 1, 'jan' => 1, 'february' => 2, 'feb' => 2, 'march' => 3, 'mar' => 3,
        'april' => 4, 'apr' => 4, 'may' => 5, 'june' => 6, 'jun' => 6, 'july' => 7, 'jul' => 7,
        'august' => 8, 'aug' => 8, 'september' => 9, 'sep' => 9, 'sept' => 9, 'october' => 10,
        'oct' => 10, 'november' => 11, 'nov' => 11, 'december' => 12, 'dec' => 12,
    ];

    // ISO: 2026-07-05
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $q, $m)) {
        [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        return checkdate($mo, $d, $y) ? ['ymd' => sprintf('%04d-%02d-%02d', $y, $mo, $d)] : null;
    }

    $monthAlt = implode('|', array_keys($months));
    $mo = 0; $d = 0; $y = null;
    if (preg_match('/^(\d{1,2})(?:st|nd|rd|th)?\s+(' . $monthAlt . ')(?:\s+(\d{4}))?$/i', $q, $m)) {
        // "5 July" / "5th July" / "5 July 2026"
        $d = (int) $m[1]; $mo = $months[strtolower($m[2])]; $y = isset($m[3]) ? (int) $m[3] : null;
    } elseif (preg_match('/^(' . $monthAlt . ')\s+(\d{1,2})(?:st|nd|rd|th)?(?:\s+(\d{4}))?$/i', $q, $m)) {
        // "July 5" / "July 5th" / "July 5 2026"
        $mo = $months[strtolower($m[1])]; $d = (int) $m[2]; $y = isset($m[3]) ? (int) $m[3] : null;
    } else {
        return null;
    }

    if ($y !== null) {
        return checkdate($mo, $d, $y) ? ['ymd' => sprintf('%04d-%02d-%02d', $y, $mo, $d)] : null;
    }
    // no year → match this month/day in any year (leap year validates Feb 29)
    return checkdate($mo, $d, 2000) ? ['md' => sprintf('%02d-%02d', $mo, $d)] : null;
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
