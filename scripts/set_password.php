<?php
/**
 * Change (or create) an admin login.   CLI ONLY — never reachable over the web.
 *
 *   php scripts/set_password.php <username> <new-password>
 *
 * Example:
 *   php scripts/set_password.php bob 'a long pass phrase you will remember'
 *
 * The password is stored as a password_hash() digest, never in plain text.
 * On a container host (Render, Fly, …) run it inside the running instance's
 * shell — the change lives in the database, so on an ephemeral disk it resets
 * with every deploy unless a persistent disk is attached (see the README).
 */

if (PHP_SAPI !== 'cli') {                       // belt and braces: the web can't run this
    http_response_code(404);
    exit("Not found.\n");
}

require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

$username = $argv[1] ?? '';
$password = $argv[2] ?? '';

if ($username === '' || $password === '') {
    fwrite(STDERR, "Usage: php scripts/set_password.php <username> <new-password>\n");
    fwrite(STDERR, "Quote the password if it contains spaces or symbols.\n");
    exit(1);
}

if (mb_strlen($password) < 8) {
    fwrite(STDERR, "Refusing: please use at least 8 characters (a passphrase is ideal).\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$exists = $pdo->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
$exists->execute([$username]);
$id = $exists->fetchColumn();

if ($id !== false) {
    $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([$hash, $id]);
    echo "OK  password updated for admin '$username'.\n";
} else {
    $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')->execute([$username, $hash]);
    echo "OK  new admin '$username' created.\n";
}

$total = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
echo "    admins in the database: $total\n";
echo "    sign in at " . (defined('SITE_URL') ? SITE_URL : '') . "/admin/\n";
