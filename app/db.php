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
        if (!defined('LOAD_SAMPLE_DATA') || LOAD_SAMPLE_DATA) {
            $pdo->exec(file_get_contents(__DIR__ . '/../db/seed.sql'));
        }
        if ((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0) {
            $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')
                ->execute(['bob', password_hash('changeme', PASSWORD_DEFAULT)]);
        }
    } else {
        // self-heal an older database: make sure the post_number column exists
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
