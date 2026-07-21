<?php
/** Receives a feedback submission → goes to the admin inbox (private). */
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

require_post();
csrf_check();

$body  = clip($_POST['body'] ?? '', 2000);
$email = clip($_POST['email'] ?? '', 120);
$back  = safe_local_redirect($_SERVER['HTTP_REFERER'] ?? null);

if ($body !== '') {
    $stmt = $pdo->prepare('INSERT INTO feedback (body, email) VALUES (?, ?)');
    $stmt->execute([$body, $email !== '' ? $email : null]);
    flash_set('Thanks for the feedback — Bob will see it.');
}

redirect($back);
