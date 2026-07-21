<?php
require __DIR__ . '/../app/admin.php';

$pdo->prepare('INSERT OR IGNORE INTO admins (username, password_hash) VALUES (?, ?)')->execute([
    'bob',
    password_hash('changeme', PASSWORD_DEFAULT),
]);

if (!admin_login('bob', 'changeme')) {
    fwrite(STDERR, "admin login should succeed\n");
    exit(1);
}

if (empty($_SESSION['admin_id'])) {
    fwrite(STDERR, "session should contain admin_id\n");
    exit(1);
}

admin_logout();

echo "admin auth check passed\n";
