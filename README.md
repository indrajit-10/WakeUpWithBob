# Wake up with Bob

An admin-curated Q&A feed. Bob posts one question each morning; the public
(no login) reads, comments, and searches. Every comment is held for Bob's
approval before it appears. Plain **PHP + PDO + SQLite** — no framework, no
build step.

## Run it locally

You need **PHP 8.1+** (the `pdo_sqlite` extension is on by default; XAMPP
includes it).

Just start the site — the database builds itself on the first page load, so
there is **no setup step**:

```bash
php -S localhost:8000 -t public
```

On Windows with XAMPP, use the full path to PHP:
```powershell
& "C:\xampp\php\php.exe" -S localhost:8000 -t public
```

Then open **http://localhost:8000**. The first request automatically creates
`data/mornings.db` with sample posts and a default admin.

*(Optional: `php scripts/install.php` does the same setup up front. For a real
deployment with no sample data, set `define('LOAD_SAMPLE_DATA', false);` in
`app/config.php`.)*

**Admin:** go to **http://localhost:8000/admin/** and log in with
**username `bob` / password `changeme`** — change it before deploying.

## Deploying (Docker / Render)

There's a root **`Dockerfile`** (based on `php:8.3-apache`) for one-click
container hosts like Render:

- points Apache's document root at `public/`,
- listens on the host's `$PORT` (defaults to `10000`),
- builds `app/config.php` from `app/config.example.php` at image build time
  (the real `config.php` is git-ignored, so it isn't in the repo),
- makes `data/` and `public/uploads/` writable.

`pdo_sqlite` is built into the official PHP image, so there are no extensions
to install. On **Render's free tier the disk is ephemeral**, so the SQLite
database rebuilds itself from `db/schema.sql` + `db/seed.sql` on every deploy —
fine for a demo; attach a persistent disk (mounted at `/var/www/html/data`) for
durable data.

## What's included

**Public**
- **Feed (`/`)** — the **last 7 days** of mornings (older ones live in the
  archive). Two views: **Latest** (newest first) and **Most Engaging** (most
  commented). Each post shows its **date** and its top comment.
- **Search** (top bar) — searches the **title and body** of every morning,
  all-time, and is also **date-aware**. See [Search](#search) below.
- **Question page (`/question.php?id=…`)** — the full question with a share bar,
  a fully threaded comment tree (reply to any comment or reply), and a comment
  form. Comments are held for approval — right after you post you see a
  **dismissible pop-up copy of your own comment** marked *"waiting for Bob to
  read it"* (only you see it, only until Bob approves).
- **Archive (`/archive.php`)** — a browsable index of every past morning,
  **one month at a time**: a month/year jump menu plus prev/next-month arrows,
  with permanent post numbers.
- **About Bob (`/about.php`)** — who Bob is, how the daily-question loop works,
  and a live stats strip.
- **FAQ / Contact / Privacy / Terms.** The Contact form drops into the admin
  **Feedback** inbox tagged `[Contact]`.
- **Light / dark toggle** in the top bar (remembers your choice; otherwise
  follows the OS), and a **hamburger menu** on mobile that holds the nav plus
  the *Ask Bob* / *Feedback* boxes.
- A site-wide **footer** — Explore (Archive), Help (FAQ, Contact), and the
  123 Greetings links (eCards, Message Board).

**Admin** (`/admin/`, behind a hashed-password login)
- Publish, **edit**, and delete morning questions. Each post has a **permanent
  number** (#1 for the first ever, counting up), with a paginated history.
- **Stats** — headline totals plus a per-post breakdown of comments and pending
  approvals, sortable by newest / most discussed.
- **Moderation** — comments **grouped by post**, in two tabs:
  - **Incoming** — approve / reject / "Approve all on this post".
  - **All comments** — every published comment badged **Replied** (reply thread
    shown inline) or **Needs reply**, filterable.
- **Reader questions** and **Feedback** inboxes.
- **User Search Data** — an anonymous, **day-by-day** report of what readers
  searched and how many results came back, browsed by month. Days with no
  searches are shown too, marked *"No searches for the day."*

## Search

One box, two matchers, combined:

- **Keyword (title + body, never comments), ranked.** The query is split into
  words; a post matches if it contains **any** word, and results are **ranked by
  how many words match** (best first) — so a multi-word search never dead-ends
  just because no single post has every word. The user's own `%`/`_` are escaped.
- **Date-aware.** `5 July` / `5th July` / `July 5` → posts made on **July 5, in
  any year**; ISO `2026-07-05` → that **exact** date. Date hits rank to the top,
  and a date query also pulls in posts whose text mentions those words. Bare
  words like `July` or `5` are **not** mistaken for dates.
- If a search matches nothing, the feed shows the **all-time most-commented**
  morning under a friendly note instead of an empty page.

Every non-empty search is logged **anonymously** (the query, the result count,
and a timestamp — no name, no cookie tie-in) for the admin **User Search Data**
report. *(Because search is a `GET` request, the query — like on most sites —
also appears in ordinary web-server access logs.)*

## Security

**Baseline**
- **Prepared statements everywhere** (SQL-injection safe) — including the
  dynamically-built search query, whose every value is bound, never concatenated.
- **All output escaped** with `htmlspecialchars` (`e()`) — XSS safe.
- **CSRF tokens** on every form and POST handler (`hash_equals`). POST-only
  endpoints reject stray GETs; a wrong/expired token returns `400`.
- Admin pages are gated on a hashed-password login; passwords use
  `password_hash`, login **regenerates the session id** (anti session-fixation)
  and slows failed attempts.
- The session cookie is **HttpOnly / SameSite=Lax**, and flips to **Secure**
  automatically over HTTPS.
- Free-text input is length-capped; admin image URLs must be real `http(s)` links.
- The SQLite file and secrets live outside the public web folder, with deny-all
  `.htaccess` safety nets in `app/`, `data/`, `db/`, `scripts/`.

**Hardening** (site-wide, centralised so it applies to every response)
- **HTTP security headers** (`send_security_headers()` in `app/helpers.php`,
  called from `app/db.php`): a strict **Content-Security-Policy** with
  `script-src 'self'` — the app ships **no inline JavaScript** (all behaviour is
  in `public/assets/js/app.js` via event delegation + `data-` attributes), so
  injected `<script>`/`on*` payloads won't run — plus `object-src 'none'`,
  `base-uri 'self'`, `form-action 'self'`, `frame-ancestors 'self'`, and
  `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`,
  `Referrer-Policy: strict-origin-when-cross-origin`.
- **CSS-context-safe image URLs** (`css_url_value()`): an admin image URL placed
  into an inline `background-image:url('…')` is percent-encoded so a stray quote
  can't break out of the CSS string.
- **Same-site redirects only** (`safe_local_redirect()`): the public comment /
  ask / feedback endpoints bounce back to a *validated local path* instead of
  trusting the client-supplied `Referer`, closing an open-redirect vector.
- **No error leakage.** With `DEBUG` off (the production default in
  `config.example.php`), uncaught errors are logged and the visitor sees a plain
  message — stack traces and file paths never reach the page.

### Before you go live
- **Change the admin password** (default `bob` / `changeme`).
- Set `CONTACT_EMAIL` (and confirm the `G123_*` footer links) in `app/config.php`.
- Serve over **HTTPS** (the Secure cookie flag then turns on by itself).
- Keep **`DEBUG` off** in `app/config.php` (the default) so errors are logged,
  not shown; setting `display_errors = Off` in `php.ini` too is belt-and-braces.
- Set `define('LOAD_SAMPLE_DATA', false);` in `app/config.php` for a clean start.
- Point the web server's document root at **`public/`** (never the project root).
- Consider a CAPTCHA / rate-limit on the public comment & feedback forms (and,
  if wanted, on search) if spam or noise becomes an issue.

## Project layout

```
public/         ← the ONLY folder the web server exposes (pages + assets)
  admin/        ← admin dashboard (posts, stats, moderation, User Search Data)
  assets/js/    ← app.js (UI behaviour) + theme-init.js (pre-paint theme)
app/            ← shared PHP kept OUTSIDE the web root
  views/        ← header, footer, leftnav, mobilemenu, sidebar, post-card
  helpers.php   ← e(), CSRF, security headers, search-date parsing, …
  db.php        ← config + hardening + the shared $pdo (self-builds the DB)
  admin.php     ← login + question/comment/moderation helpers
data/           ← the SQLite database file (outside the web root, git-ignored)
db/             ← schema.sql (table blueprint) + seed.sql (sample data)
scripts/        ← install.php (one-time setup) + test_admin.php
Dockerfile      ← container build for Render / any Docker host
```

`app/config.php` and `data/*.db` are git-ignored — copy
`app/config.example.php` to `app/config.php` on a new machine and fill in real
values.

## Still to build

The last piece not yet wired is **email notifications to Bob** (SMTP settings
already have a home in `app/config.php`). Everything linked in the menus —
feed, question threads, search, archive, about, "Ask Bob a question", and
"Send feedback" — is built and working.
