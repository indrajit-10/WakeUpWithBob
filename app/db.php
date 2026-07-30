<?php
/**
 * Opens the one shared database connection: $pdo.
 * Every page does `require` on this file, then uses $pdo to read/write.
 *
 * On the very first request it builds the whole database automatically
 * (tables + sample data + a default admin) — so you can just run the site,
 * no setup step needed.
 */
require_once __DIR__ . '/config.php';

// Timestamps are stored in UTC; render them in the operator's timezone so
// "just now" / dates line up with the wall clock. Defaults to UTC.
date_default_timezone_set(
    defined('APP_TIMEZONE') && in_array(APP_TIMEZONE, timezone_identifiers_list(), true)
        ? APP_TIMEZONE
        : 'UTC'
);

// --- fail safe: never leak stack traces or file paths to visitors ------------------
// Set  define('DEBUG', true);  in config.php while developing to see full errors on screen.
$__debug = defined('DEBUG') && DEBUG;
@ini_set('display_errors', $__debug ? '1' : '0');
error_reporting(E_ALL);
if (!$__debug && PHP_SAPI !== 'cli') {
    // Buffer the page so a mid-render exception can still set a real 500 and replace
    // the half-written HTML. Without this the status was already committed and broken
    // pages went out as "200 OK" with a truncated body — invisible to uptime checks.
    ob_start();
    set_exception_handler(static function (\Throwable $e): void {
        error_log((string) $e);                          // logged for the operator, never shown
        while (ob_get_level() > 0) {                     // discard the partial page
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
        }
        echo 'Something went wrong on our end. Please try again in a moment.';
    });
}

// --- hardening HTTP headers on every response (CSP, nosniff, frame + referrer policy) ---
if (function_exists('send_security_headers')) {
    send_security_headers();
}

@mkdir(dirname(DB_PATH), 0775, true);                     // make sure the data/ folder exists
$db_is_fresh = !file_exists(DB_PATH) || filesize(DB_PATH) === 0;

try {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);          // fail loudly, don't hide errors
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);      // rows come back as $row['title']
    $pdo->exec('PRAGMA foreign_keys = ON');                                 // enforce the table relationships

    if ($db_is_fresh) {
        // first run — build everything
        $pdo->exec(file_get_contents(__DIR__ . '/../db/schema.sql'));
        // Sample posts, only when asked for. Tolerate a missing/empty seed.sql: a
        // deployer removing it to force a clean site must not fatal the first request.
        if (!defined('LOAD_SAMPLE_DATA') || LOAD_SAMPLE_DATA) {
            $seedFile = __DIR__ . '/../db/seed.sql';
            $seed = is_readable($seedFile) ? (string) file_get_contents($seedFile) : '';
            if (trim($seed) !== '') {
                $pdo->exec($seed);
            }
        }
        if ((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0) {
            $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')
                ->execute(['bob', password_hash('changeme', PASSWORD_DEFAULT)]);
        }
    } else {
        // self-heal an existing database. schema.sql is all "CREATE ... IF NOT EXISTS",
        // so re-running it is a no-op for existing tables but creates any NEW ones (e.g.
        // the searches table) that a database built before this version is missing.
        $pdo->exec(file_get_contents(__DIR__ . '/../db/schema.sql'));

        // older databases also need the post_number column backfilled
        $hasPostNumber = (int) $pdo->query(
            "SELECT COUNT(*) FROM pragma_table_info('questions') WHERE name = 'post_number'"
        )->fetchColumn();
        if ($hasPostNumber === 0) {
            $pdo->exec('ALTER TABLE questions ADD COLUMN post_number INTEGER');
            $ids = $pdo->query('SELECT id FROM questions ORDER BY created_at ASC, id ASC')->fetchAll(PDO::FETCH_COLUMN);
            $n = 1;
            $upd = $pdo->prepare('UPDATE questions SET post_number = ? WHERE id = ?');
            foreach ($ids as $qid) { $upd->execute([$n++, $qid]); }
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database error. Please make sure the data/ folder is writable.');
}
