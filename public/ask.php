<?php
/** Receives an "Ask Bob a question" submission → goes to the admin inbox (private). */
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

require_post();
csrf_check();

$body  = clip($_POST['body'] ?? '', 2000);
$email = clip($_POST['email'] ?? '', 120);
$back  = $_SERVER['HTTP_REFERER'] ?? '/';

if ($body !== '') {
    $stmt = $pdo->prepare('INSERT INTO reader_questions (body, email) VALUES (?, ?)');
    $stmt->execute([$body, $email !== '' ? $email : null]);
    // (an email alert to Bob would fire here once SMTP is wired up)
    flash_set('Thanks — your question is on its way to Bob.');
}

redirect($back);
