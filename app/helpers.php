<?php
/** Small shared helpers used across pages. */

/** Escape text before printing it into HTML (prevents XSS / broken markup).
 *  ENT_SUBSTITUTE matters: without it htmlspecialchars returns an EMPTY STRING for
 *  any text containing an invalid UTF-8 byte, so one bad byte pasted from Word or a
 *  legacy app would silently blank a whole post or comment. With it, the stray byte
 *  becomes U+FFFD and the rest of the text survives. */
function e(mixed $s): string {
    // Accept mixed on purpose: request values are printed all over the app, and a
    // crafted "?q[]=x" would otherwise make this throw a TypeError and 500 the page.
    // Arrays/objects have no sensible rendering, so they become ''.
    if (is_array($s) || is_object($s)) {
        return '';
    }
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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

/** A random token, generated once per request, that whitelists the handful of inline
 *  <script> blocks we genuinely need (currently only the Google Analytics bootstrap).
 *  A nonce is far safer than 'unsafe-inline': it permits the exact block we tagged
 *  and still blocks any script an attacker manages to inject, because they cannot
 *  guess this value. Echo it as  nonce="<?= e(csp_nonce()) ?>"  on the script tag. */
function csp_nonce(): string {
    static $nonce = null;
    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(16));
    }
    return $nonce;
}

/** Send hardening HTTP headers on every web response (no-op on CLI or once output has begun). */
function send_security_headers(): void {
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }
    // script-src stays tight: 'self' for our own files, a per-request nonce for the
    // one inline Google Analytics block, and googletagmanager.com for gtag.js. An
    // injected <script> still cannot run — it has neither our origin nor the nonce.
    // connect-src must be listed explicitly (default-src would otherwise block it)
    // so gtag can POST measurements; wildcards cover Google's regional endpoints.
    // Inline styles are limited to a static SVG and background-image URLs, so style
    // keeps 'unsafe-inline'. External images are allowed over https.
    header("Content-Security-Policy: "
        . "default-src 'self'; "
        . "base-uri 'self'; "
        . "object-src 'none'; "
        . "frame-ancestors 'self'; "
        . "form-action 'self'; "
        . "img-src 'self' https: data:; "
        . "style-src 'self' 'unsafe-inline'; "
        . "connect-src 'self' https://*.google-analytics.com https://*.analytics.google.com https://www.googletagmanager.com; "
        . "script-src 'self' 'nonce-" . csp_nonce() . "' https://www.googletagmanager.com");
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
function clean_http_url(mixed $url): string {
    if (!is_string($url)) {
        return '';        // array-shaped or missing input is simply "no URL"
    }
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

/** Read an admin-editable setting from the database (or $default if unset / no DB yet).
 *  Uses the shared $pdo; safe to call before the settings table exists (returns $default). */
function setting_get(string $key, ?string $default = null): ?string {
    global $pdo;
    if (!($pdo instanceof PDO)) {
        return $default;
    }
    try {
        $stmt = $pdo->prepare('SELECT value FROM settings WHERE key = ? LIMIT 1');
        $stmt->execute([$key]);
        $v = $stmt->fetchColumn();
        return $v === false ? $default : (string) $v;
    } catch (\Throwable $e) {
        return $default;   // e.g. an older database without the settings table
    }
}

/** Save an admin-editable setting (insert or update). */
function setting_set(string $key, string $value): void {
    global $pdo;
    if (!($pdo instanceof PDO)) {
        return;
    }
    $pdo->prepare(
        'INSERT INTO settings (key, value) VALUES (?, ?)
         ON CONFLICT(key) DO UPDATE SET value = excluded.value'
    )->execute([$key, $value]);
}

/** The inbox that should receive alerts: the admin-set override if present and valid,
 *  otherwise the ADMIN_EMAIL from config/env. Empty string when nothing is configured. */
function alert_recipient(): string {
    $override = trim((string) setting_get('alert_email', ''));
    if ($override !== '' && filter_var($override, FILTER_VALIDATE_EMAIL)) {
        return $override;
    }
    return defined('ADMIN_EMAIL') ? trim((string) ADMIN_EMAIL) : '';
}

/** Best-effort admin alert email via the Resend API. Never throws and never blocks the
 *  caller: a failure is logged and returns false. The recipient is the admin Settings
 *  override if set, else ADMIN_EMAIL. When MAIL_ENABLED is off or there's no API key, the
 *  message is written to data/mail.log instead of sent, so local development can verify the
 *  flow without a mail provider. */
function send_admin_alert(string $subject, string $html, string $text = ''): bool {
    $to = alert_recipient();
    if ($to === '') {
        return false;
    }
    $from = defined('MAIL_FROM') ? (string) MAIL_FROM : 'alerts@example.com';
    if ($text === '') {
        $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'));
    }

    $enabled = defined('MAIL_ENABLED') && MAIL_ENABLED;
    $key     = defined('RESEND_API_KEY') ? (string) RESEND_API_KEY : '';

    // Disabled / no key / no curl — log the message instead of sending.
    if (!$enabled || $key === '' || !function_exists('curl_init')) {
        if (defined('DB_PATH')) {
            @file_put_contents(
                dirname(DB_PATH) . '/mail.log',
                '[' . date('c') . "]\nTO: $to\nSUBJECT: $subject\n$text\n" . str_repeat('-', 48) . "\n",
                FILE_APPEND
            );
        }
        return false;
    }

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'from' => $from, 'to' => [$to], 'subject' => $subject, 'html' => $html, 'text' => $text,
        ]),
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $key, 'Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT        => 8,
    ]);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($code < 200 || $code >= 300) {
        error_log('send_admin_alert: Resend failed (HTTP ' . $code . ') ' . ($err !== '' ? $err : (string) $resp));
        return false;
    }
    return true;
}

/** SQL condition for "this post is live to the public".
 *  A post's created_at doubles as its publish time, so a future date = scheduled
 *  (hidden until it arrives) and a past date = backdated into the archive.
 *  SQLite's datetime('now') is UTC, and created_at is stored UTC, so they compare
 *  directly. Use this in EVERY public query — without it, scheduled posts leak. */
function published_sql(string $col = 'created_at'): string {
    return "$col <= datetime('now')";
}

/** SQLite datetime modifier arguments for APP_TIMEZONE, e.g. "'+5 hours','+30 minutes'".
 *  NOTE: SQLite needs each modifier as a SEPARATE argument — a single
 *  '+5 hours 30 minutes' string makes datetime() return NULL. Built from integers
 *  via sprintf, so the result is always a safe literal (never user input). */
function tz_sql_offset(?string $refUtc = null): string {
    $tzName = defined('APP_TIMEZONE') ? APP_TIMEZONE : 'UTC';
    try {
        $ref = new DateTime($refUtc ?? 'now', new DateTimeZone('UTC'));
        $off = (new DateTimeZone($tzName))->getOffset($ref);   // seconds east of UTC
    } catch (\Throwable $e) {
        $off = 0;
    }
    $sign = $off < 0 ? '-' : '+';
    $off  = abs($off);
    return sprintf("'%s%d hours','%s%d minutes'", $sign, intdiv($off, 3600), $sign, intdiv($off % 3600, 60));
}

/** SQL expression shifting a stored-UTC column into APP_TIMEZONE.
 *  PREFER local_day_utc_range()/local_month_utc_range() for grouping and filtering:
 *  this applies ONE offset (taken at $refUtc, or today) to every row, which in a
 *  DST zone is an hour wrong for roughly half the year — enough to file a post in
 *  the wrong month. Kept only for cases where a per-row offset is not needed. */
function local_datetime_sql(string $col, ?string $refUtc = null): string {
    return "datetime($col, " . tz_sql_offset($refUtc) . ")";
}

/** The UTC half-open range [start, end) covering one LOCAL calendar day.
 *  DST-exact, because PHP resolves the offset for that specific date. Compare with
 *  `created_at >= ? AND created_at < ?` — index-friendly and always correct. */
function local_day_utc_range(string $ymd): ?array {
    return local_period_utc_range($ymd . ' 00:00:00', '+1 day');
}

/** The UTC half-open range [start, end) covering one LOCAL month ('YYYY-MM'). */
function local_month_utc_range(string $ym): ?array {
    return local_period_utc_range($ym . '-01 00:00:00', '+1 month');
}

/** Shared worker: a local wall-clock start plus a relative length → UTC bounds. */
function local_period_utc_range(string $localStart, string $length): ?array {
    $tz = defined('APP_TIMEZONE') ? APP_TIMEZONE : 'UTC';
    try {
        $zone  = new DateTimeZone($tz);
        $start = new DateTime($localStart, $zone);
        $end   = (clone $start)->modify($length);
        if ($end <= $start) {
            return null;
        }
        $utc = new DateTimeZone('UTC');
        return [
            (clone $start)->setTimezone($utc)->format('Y-m-d H:i:s'),
            (clone $end)->setTimezone($utc)->format('Y-m-d H:i:s'),
        ];
    } catch (\Throwable $e) {
        return null;
    }
}

/** Group rows by the LOCAL month they fall in, exactly (per-row, DST-safe).
 *  $rows is a list of stored UTC datetime strings. Returns ['YYYY-MM' => count],
 *  newest month first — the shape the archive/report month pickers want. */
function count_by_local_month(array $rows): array {
    $out = [];
    foreach ($rows as $utc) {
        $ym = date('Y-m', db_time((string) $utc));
        $out[$ym] = ($out[$ym] ?? 0) + 1;
    }
    krsort($out);
    return $out;
}

/** Convert an admin-entered local datetime ("2026-07-05T08:00" from an
 *  <input type="datetime-local">) into the 'Y-m-d H:i:s' UTC string the database
 *  stores. Returns null when the input is empty or unparseable, so the caller can
 *  fall back to "now". APP_TIMEZONE decides what the typed time means — without
 *  this conversion an early-morning time would land on the wrong day. */
function local_input_to_utc(mixed $local): ?string {
    if (!is_string($local)) {
        return null;      // array-shaped input = "no date given"
    }
    $local = trim($local);
    if ($local === '') {
        return null;
    }
    // Only accept exactly what <input type="datetime-local"> emits. new DateTime()
    // otherwise honours free text like "+8000 years", "tomorrow" or "now", which put
    // unusable values in created_at: a 5-digit year breaks the TEXT comparison in
    // published_sql() (a "scheduled" post reads as live) and makes SQLite's datetime()
    // return NULL, which crashed the public archive mid-page.
    if (!preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(:\d{2})?$/', $local)) {
        return null;
    }
    $tz = defined('APP_TIMEZONE') ? APP_TIMEZONE : 'UTC';
    try {
        $dt = new DateTime($local, new DateTimeZone($tz));
    } catch (\Throwable $e) {
        return null;
    }
    $dt->setTimezone(new DateTimeZone('UTC'));
    return $dt->format('Y-m-d H:i:s');
}

/** The reverse: a stored UTC datetime as a local value for a datetime-local input. */
function utc_to_local_input(?string $utc): string {
    $utc = trim((string) $utc);
    if ($utc === '') {
        return '';
    }
    $tz = defined('APP_TIMEZONE') ? APP_TIMEZONE : 'UTC';
    try {
        $dt = new DateTime($utc, new DateTimeZone('UTC'));
    } catch (\Throwable $e) {
        return '';
    }
    $dt->setTimezone(new DateTimeZone($tz));
    return $dt->format('Y-m-d\TH:i');
}

/** True when a stored (UTC) publish time is still in the future — i.e. scheduled. */
function is_scheduled(?string $utc): bool {
    $utc = trim((string) $utc);
    return $utc !== '' && db_time($utc) > time();
}

/** Sentinels used to mark search hits BEFORE escaping. They are control characters,
 *  so htmlspecialchars passes them through untouched and they cannot be confused with
 *  real text (any that somehow appear in the source are stripped first). */
const HL_OPEN  = "\x02";
const HL_CLOSE = "\x03";

/** Wrap every occurrence of $terms in the RAW text with sentinels.
 *  Marking before escaping is what keeps highlighting safe: we never inject markup
 *  into rendered HTML, so searching for "strong", "em" or "quot" can't corrupt a tag
 *  or an entity, and the terms themselves are escaped along with everything else. */
function mark_search_terms(string $text, array $terms): string {
    $text = str_replace([HL_OPEN, HL_CLOSE], '', $text);   // no forging a <mark> from post text
    $clean = [];
    foreach ($terms as $t) {
        $t = trim((string) $t);
        if ($t !== '') {
            $clean[] = $t;
        }
    }
    if (!$clean) {
        return $text;
    }
    // longest first, so "good morning" wins over "good" where both match
    usort($clean, static fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
    $alt = implode('|', array_map(static fn ($t) => preg_quote($t, '/'), $clean));
    $out = preg_replace('/(' . $alt . ')/iu', HL_OPEN . '$1' . HL_CLOSE, $text);
    return $out ?? $text;     // regex failure (e.g. bad UTF-8) → unhighlighted, never broken
}

/** Turn the sentinels into <mark> once the text is already escaped and formatted. */
function apply_highlight(string $escapedHtml): string {
    return str_replace([HL_OPEN, HL_CLOSE], ['<mark>', '</mark>'], $escapedHtml);
}

/** Escape a short plain string (e.g. a post title) with search hits highlighted. */
function e_highlight(mixed $text, array $terms = []): string {
    if (!$terms || is_array($text) || is_object($text)) {
        return e($text);
    }
    return apply_highlight(e(mark_search_terms((string) $text, $terms)));
}

/** Render an author's post body as safe HTML with light Markdown-style emphasis.
 *
 *  SECURITY: the text is escaped FIRST with e(), so any HTML the author typed is
 *  already inert (&lt;script&gt;) before we touch it. Only then do we introduce a
 *  fixed whitelist of tags — <strong>, <em>, <u> — built by us, never by the input.
 *  There is therefore no path for raw markup or an on* attribute to reach the page.
 *  Line breaks are NOT converted here: `white-space:pre-wrap` on .post-body
 *  already preserves the author's newlines and blank lines exactly as typed.
 *
 *  Supports  **bold**  __bold__  *italic*  _italic_  ++underline++  — markers must
 *  hug their text (`**like this**`, not `** like this **`) and cannot span lines, so
 *  ordinary prose, maths and snake_case identifiers are left alone. (Markdown has no
 *  underline syntax; ++…++ is our own, matching the admin editor's U button.) */
function format_post_text(?string $text, array $highlight = []): string {
    $raw  = (string) $text;
    // Mark search hits in the RAW text first; the sentinels survive escaping and only
    // become <mark> at the very end, so highlighting never widens what HTML can appear.
    if ($highlight) {
        $raw = mark_search_terms($raw, $highlight);
    }
    $html = e($raw);                     // escape first — nothing raw survives this
    $rules = [
        // bold before italic, so ** is never mistaken for a pair of single *
        '/\*\*(?!\s)([^*\n]+?)(?<!\s)\*\*/u'              => '<strong>$1</strong>',
        '/(?<![\w_])__(?!\s)([^_\n]+?)(?<!\s)__(?![\w_])/u' => '<strong>$1</strong>',
        '/\+\+(?!\s)([^+\n]+?)(?<!\s)\+\+/u'                => '<u>$1</u>',
        '/(?<![\w*])\*(?!\s)([^*\n]+?)(?<!\s)\*(?![\w*])/u' => '<em>$1</em>',
        '/(?<![\w_])_(?!\s)([^_\n]+?)(?<!\s)_(?![\w_])/u'   => '<em>$1</em>',
    ];
    foreach ($rules as $pattern => $replacement) {
        $out = preg_replace($pattern, $replacement, $html);
        if ($out !== null) {             // on a regex failure keep the safe escaped text
            $html = $out;
        }
    }
    return $highlight ? apply_highlight($html) : $html;
}

/** Strip Markdown emphasis markers for plain-text contexts (list labels, email
 *  subjects) so an excerpt never shows stray ** or _ characters. */
function strip_post_markup(?string $text): string {
    $t = (string) $text;
    $out = preg_replace('/(\*\*|__|\+\+|\*|_)/u', '', $t);
    return $out ?? $t;
}

/** A display label for a post whose title is optional.
 *  Titles are not required — a morning can be just a piece of writing — but list
 *  views (archive, admin history, moderation headers) still need something
 *  clickable to show. Falls back to a short excerpt of the body, then to the
 *  permanent post number. Returns plain text; escape it at the point of output. */
function post_label(?string $title, ?string $body = null, int|string|null $postNumber = null, int $max = 70): string {
    $title = trim((string) $title);
    if ($title !== '') {
        return $title;
    }
    // No title — use the opening of the post itself, collapsed onto one line
    // (with any **bold** / _italic_ markers removed, since this is plain text).
    $body = trim(strip_post_markup($body));
    if ($body !== '') {
        $oneLine = trim((string) preg_replace('/\s+/u', ' ', $body));
        if ($oneLine !== '') {
            return mb_strimwidth($oneLine, 0, $max, '…');
        }
    }
    $n = (int) $postNumber;
    return $n > 0 ? 'Morning #' . $n : 'Untitled morning';
}

/** Parse a stored datetime (SQLite writes UTC) into an absolute Unix timestamp.
 *  Interpreting it as UTC — not the server's local zone — keeps "X ago" honest
 *  regardless of where the server runs. */
function db_time(string $datetime): int {
    $ts = strtotime($datetime . ' UTC');
    if ($ts === false) {
        $ts = strtotime($datetime);   // fall back for any non-standard stored value
    }
    return $ts !== false ? $ts : time();
}

/** Friendly "3 hr. ago" style timestamp from a stored (UTC) datetime string. */
function time_ago(string $datetime): string {
    $ts   = db_time($datetime);
    $diff = time() - $ts;
    if ($diff < 0)      $diff = 0;             // tiny clock skew → "just now", never "in the future"
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60)   . ' min. ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' hr. ago';
    if ($diff < 172800) return '1 day ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j', $ts);
}

/** Exact, human-readable timestamp (in APP_TIMEZONE) from a stored UTC datetime,
 *  e.g. "Jul 24, 2026 · 2:05 PM". Used where an absolute time matters (moderation). */
function fmt_datetime(string $datetime): string {
    return date('M j, Y · g:i a', db_time($datetime));
}
