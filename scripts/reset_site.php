<?php
/**
 * Reset the site to a brand-new state.   CLI ONLY — never reachable over the web.
 *
 * Deletes ALL content: every post, every comment (approved and pending), every
 * reader question, every feedback/contact message and the search log. Counters and
 * id sequences are reset too, so the first post you publish afterwards is #1.
 *
 * Kept by default: your admin login(s) and the alert-email set in admin Settings.
 * Sample/seed data is never re-created — the site stays empty until you publish.
 *
 *   php scripts/reset_site.php                       # dry run: show what WOULD go
 *   php scripts/reset_site.php --yes                 # actually wipe the content
 *   php scripts/reset_site.php --yes --admin bob --password 'a long pass phrase'
 *                                                    # wipe AND set the admin login
 *   php scripts/reset_site.php --yes --wipe-admins    # also remove admin logins
 *                                                    # (a default bob/changeme is
 *                                                    #  recreated on next page load)
 *
 * Take a copy of data/mornings.db first — this cannot be undone.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not found.\n");
}

require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

$args        = array_slice($argv, 1);
$confirmed   = in_array('--yes', $args, true);
$wipeAdmins  = in_array('--wipe-admins', $args, true);
$adminUser   = null;
$adminPass   = null;
foreach ($args as $i => $a) {
    if ($a === '--admin')    { $adminUser = $args[$i + 1] ?? null; }
    if ($a === '--password') { $adminPass = $args[$i + 1] ?? null; }
}

/** Content tables, in an order that respects the foreign keys. */
$contentTables = ['comments', 'questions', 'reader_questions', 'feedback', 'searches'];

echo "Database: " . DB_PATH . "\n\n";
echo "Current contents\n";
echo "----------------\n";
$counts = [];
foreach (array_merge($contentTables, ['admins', 'settings']) as $t) {
    try {
        $counts[$t] = (int) $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
    } catch (\Throwable $e) {
        $counts[$t] = -1;                                  // table not there yet
    }
    printf("  %-18s %s\n", $t, $counts[$t] < 0 ? '(missing)' : $counts[$t] . ' rows');
}

$toDelete = 0;
foreach ($contentTables as $t) {
    $toDelete += max(0, $counts[$t]);
}

if (!$confirmed) {
    echo "\nDRY RUN — nothing has been changed.\n";
    echo "Would delete $toDelete content row(s) from: " . implode(', ', $contentTables) . ".\n";
    echo "Would keep: admins (" . max(0, $counts['admins']) . "), settings (" . max(0, $counts['settings']) . ").\n\n";
    echo "Re-run with --yes to do it:\n";
    echo "  php scripts/reset_site.php --yes\n";
    exit(0);
}

echo "\nResetting…\n";
$pdo->beginTransaction();
try {
    $pdo->exec('PRAGMA foreign_keys = OFF');               // wipe order then can't bite us
    foreach ($contentTables as $t) {
        if ($counts[$t] >= 0) {
            $pdo->exec("DELETE FROM $t");
            echo "  cleared $t\n";
        }
    }
    if ($wipeAdmins) {
        $pdo->exec('DELETE FROM admins');
        echo "  cleared admins (a default bob/changeme will be created on the next page load)\n";
    }
    // Restart id numbering so the first new post is id 1 / #1.
    try { $pdo->exec("DELETE FROM sqlite_sequence WHERE name IN ('comments','questions','reader_questions','feedback','searches')"); }
    catch (\Throwable $e) { /* no sqlite_sequence yet — nothing to reset */ }
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "FAILED, nothing was changed: " . $e->getMessage() . "\n");
    exit(1);
}

// Optionally set the admin login in the same go.
if ($adminUser !== null && $adminPass !== null) {
    if (mb_strlen($adminPass) < 8) {
        fwrite(STDERR, "Content was reset, but the password was refused: use at least 8 characters.\n");
        exit(1);
    }
    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
    $row  = $pdo->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
    $row->execute([$adminUser]);
    $id = $row->fetchColumn();
    if ($id !== false) {
        $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([$hash, $id]);
        echo "  password updated for admin '$adminUser'\n";
    } else {
        $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')->execute([$adminUser, $hash]);
        echo "  admin '$adminUser' created\n";
    }
} elseif ($adminUser !== null || $adminPass !== null) {
    echo "  note: --admin and --password must be given together; admin login left unchanged\n";
}

// Shrink the file back down. VACUUM refuses to run while any statement is still
// open, and it is only housekeeping — never let it fail the reset.
try {
    unset($row);
    $pdo->exec('VACUUM');
} catch (\Throwable $e) {
    echo "  (skipped VACUUM: " . $e->getMessage() . ")\n";
}

echo "\nDone. The site is empty.\n";
foreach (array_merge($contentTables, ['admins']) as $t) {
    try { printf("  %-18s %d rows\n", $t, (int) $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn()); }
    catch (\Throwable $e) {}
}
echo "\nNothing is public until you sign in at " . (defined('SITE_URL') ? SITE_URL : '') . "/admin/ and publish.\n";
if ((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0) {
    echo "No admin exists: loading any page recreates the default bob/changeme —\n";
    echo "change it at once with  php scripts/set_password.php bob 'a long pass phrase'\n";
}
