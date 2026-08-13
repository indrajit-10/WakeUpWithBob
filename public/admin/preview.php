<?php
/**
 * Preview one post  (  /admin/preview.php?id=N  )
 *
 * Shows a post exactly as readers will see it — including one that is still
 * scheduled and therefore 404s on its public URL.
 *
 * It deliberately does NOT re-implement the page. It sets a flag and requires
 * public/question.php, so there is only ever one template and the preview can
 * never drift from the real thing.
 *
 * app/admin.php is what makes this safe: it starts the hardened session AND
 * runs admin_enforce_idle(), so a browser left open past ADMIN_IDLE_TIMEOUT
 * cannot use this to read unpublished posts.
 */
require __DIR__ . '/../../app/admin.php';

if (!admin_logged_in()) {
    redirect('/admin/');
}

$ADMIN_PREVIEW = true;               // read by question.php to lift the published_sql() gate
require __DIR__ . '/../question.php';
