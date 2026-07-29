<?php
/**
 * Copy this file to  app/config.php  and fill in real values.
 * config.php is git-ignored, so your secrets never get committed.
 *
 * EVERY setting below can be supplied as an ENVIRONMENT VARIABLE instead of by
 * editing this file — the env var always wins, the value here is just the
 * fallback. That matters on container hosts (Render, Fly, …), where the image
 * build copies this example over to config.php: set the env vars in the host's
 * dashboard and you never have to edit or rebuild anything.
 *
 * See the README's "Going live" section for the full checklist.
 */

// Absolute path to the SQLite file — kept OUTSIDE the public web folder.
define('DB_PATH', getenv('DB_PATH') ?: dirname(__DIR__) . '/data/mornings.db');

// --- identity -------------------------------------------------------------
define('SITE_NAME', getenv('SITE_NAME') ?: 'Wake up with Bob');
// IMPORTANT: your real public address, no trailing slash (e.g. https://bob.example.com).
// Links inside the alert emails are built from this — leave it as localhost and
// every "open the inbox" link you receive will point at your own machine.
define('SITE_URL',  getenv('SITE_URL') ?: 'http://localhost:8000');

// Timezone for displaying timestamps (the database always stores UTC). Pick a
// name from https://www.php.net/timezones — e.g. 'Asia/Kolkata',
// 'America/New_York'. Leave 'UTC' if you're unsure.
define('APP_TIMEZONE', getenv('APP_TIMEZONE') ?: 'UTC');

// Development switch. Production-safe default (FALSE): errors are logged, never
// shown, so stack traces and file paths can't leak to visitors. Set APP_DEBUG=true
// (env var) locally to see them on screen.
define('DEBUG', (getenv('APP_DEBUG') ?: 'false') === 'true');

// Seed a few sample posts the FIRST time the database is built. Set
// LOAD_SAMPLE_DATA=false (env var) before the very first run for a clean site.
define('LOAD_SAMPLE_DATA', (getenv('LOAD_SAMPLE_DATA') ?: 'true') !== 'false');

// How long an admin can be idle before being signed out again (seconds).
define('ADMIN_IDLE_TIMEOUT', (int) (getenv('ADMIN_IDLE_TIMEOUT') ?: 1800));

// Where to reach a human (shown on the Contact page).
define('CONTACT_EMAIL', getenv('CONTACT_EMAIL') ?: 'hello@example.com');

// --- 123 Greetings family links shown in the footer ---
// Replace these with the exact URLs you want. They default to the real main
// site so nothing is a dead or made-up link until you customise them.
define('G123_HOME',   getenv('G123_HOME')   ?: 'https://www.123greetings.com/');
define('G123_ECARDS', getenv('G123_ECARDS') ?: 'https://www.123greetings.com/');    // free eCards
define('G123_BLOG',   getenv('G123_BLOG')   ?: 'https://blog.123greetings.com/');
define('G123_LOGO',   getenv('G123_LOGO')   ?: 'https://blog.123greetings.com/wp-content/uploads/2026/04/123greetings.webp');

// --- Email alerts: ping your inbox on new comments / questions / feedback ---
// Sent via Resend (https://resend.com), a transactional email API — pure HTTPS,
// no libraries. Set these as ENV VARS on the server; they fall back safely here.
// When MAIL_ENABLED is off (or the key is blank) messages are written to
// data/mail.log instead of sent — handy for local development.
define('ADMIN_EMAIL',    getenv('ADMIN_EMAIL') ?: 'bob@example.com');        // who receives the alerts
define('MAIL_ENABLED',   (getenv('MAIL_ENABLED') ?: 'false') === 'true');    // set MAIL_ENABLED=true to send
define('RESEND_API_KEY', getenv('RESEND_API_KEY') ?: '');                    // <-- YOUR API KEY GOES HERE (or as an env var)
define('MAIL_FROM',      getenv('MAIL_FROM') ?: 'Wake up with Bob <onboarding@resend.dev>'); // verified sender
