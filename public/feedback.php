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
    $link = (defined('SITE_URL') ? SITE_URL : '') . '/admin/feedback.php';
    send_admin_alert(
        'New feedback',
        '<p>New feedback came in:</p><blockquote>' . nl2br(e($body)) . '</blockquote>'
        . ($email !== '' ? '<p>Reply-to: ' . e($email) . '</p>' : '')
        . '<p><a href="' . e($link) . '">Open the inbox →</a></p>',
        "New feedback:\n\n$body\n" . ($email !== '' ? "Reply-to: $email\n" : '') . "\nInbox: $link"
    );
    flash_set('Thanks for the feedback — Bob will see it.');
}

redirect($back);
