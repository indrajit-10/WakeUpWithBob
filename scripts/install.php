<?php
/**
 * One-time setup. Builds the SQLite database from the schema, adds sample
 * data, and creates the first admin. Safe to run again — it won't duplicate.
 *
 * Run from the project root:  php scripts/install.php
 */
require __DIR__ . '/../app/config.php';

@mkdir(dirname(DB_PATH), 0775, true);   // make sure the data/ folder exists

$pdo = new PDO('sqlite:' . DB_PATH);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec(file_get_contents(__DIR__ . '/../db/schema.sql'));
echo "OK  tables ready\n";

if ($pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn() == 0) {
    $pdo->exec(file_get_contents(__DIR__ . '/../db/seed.sql'));
    echo "OK  sample questions added\n";
}

if ($pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() == 0) {
    $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
    $stmt->execute(['bob', password_hash('changeme', PASSWORD_DEFAULT)]);
    echo "OK  admin created  ->  username: bob   password: changeme   (change this!)\n";
}

echo "\nDone. Start the site with:\n  php -S localhost:8000 -t public\n";
