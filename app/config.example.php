<?php
/**
 * Copy this file to  app/config.php  and fill in real values.
 * config.php is git-ignored, so your secrets never get committed.
 */

// Absolute path to the SQLite file — kept OUTSIDE the public web folder.
define('DB_PATH', dirname(__DIR__) . '/data/mornings.db');

define('SITE_NAME', 'Wake up with Bob');
define('SITE_URL',  'http://localhost:8000');

// Development switch. Leave FALSE in production: errors are then logged, never shown,
// so stack traces and file paths can't leak to visitors. Set TRUE locally to see them.
define('DEBUG', false);

// Where to reach a human (shown on the Contact page).
define('CONTACT_EMAIL', 'hello@example.com');

// --- 123 Greetings family links shown in the footer ---
// Replace these with the exact URLs you want. They default to the real main
// site so nothing is a dead or made-up link until you customise them.
define('G123_HOME',   'https://www.123greetings.com/');
define('G123_ECARDS', 'https://www.123greetings.com/');    // free eCards
define('G123_BLOG',   'https://blog.123greetings.com/');
define('G123_LOGO',   'https://blog.123greetings.com/wp-content/uploads/2026/04/123greetings.webp');

// --- Email alerts (leave blank until you wire SMTP — see DEPLOY.md) ---
define('ADMIN_EMAIL', 'bob@example.com'); // where new-activity alerts are sent
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
