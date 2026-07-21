# Wake up with Bob

An admin-curated Q&A feed. Bob posts one question each morning; the public
(no login) likes, comments, and shares. Every comment is held for Bob's
approval before it appears. Plain **PHP + PDO + SQLite** — no framework, no
build step. Powered by 123 Greetings.

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

## What's included

**Public**
- Feed (`/`) — all questions, 4 sorts (latest / oldest / most popular / most
  engaging), title search, and each post's top comment.
- Question page (`/question.php?id=…`) — the full question with a like/share
  bar, a fully threaded comment tree (reply to any comment or reply), and a
  comment form. Comments are held for approval — right after you post, you see
  a **translucent copy of your own comment** marked *"waiting for Bob to read
  it"* (only you see it, only until Bob approves).
- **Likes toggle** on questions and comments — tap to like, tap again to
  un-like (one per browser, tracked by a cookie token).
- Archive (`/archive.php`) — a browsable index of every past morning, grouped
  by month, newest first, with permanent post numbers and pagination.
- About Bob (`/about.php`) — who Bob is, how the daily-question loop works, a
  live stats strip, and the 123 Greetings co-brand.
- FAQ (`/faq.php`), Contact (`/contact.php`), Privacy (`/privacy.php`), and
  Terms (`/terms.php`). The Contact form drops into the admin **Feedback**
  inbox tagged `[Contact]`.
- A site-wide **footer** on every page — navigation, a Privacy/Terms row, and
  links to the 123 Greetings family (main site, eCards, blog).

**Admin** (`/admin/`, behind a hashed-password login)
- Publish, **edit**, and delete morning questions.
- Every post has a **permanent number** (#1 for the first ever, counting up).
- Full post **history with pagination** (10 per page).
- **Stats** — headline totals plus a per-post breakdown of likes, comments, and
  pending approvals, sortable by newest / most liked / most discussed.
- **Moderation** — one page, comments **grouped by post**, in two tabs:
  - **Incoming** — approve / reject / "Approve all on this post". Approving
    publishes the comment and moves it to All comments.
  - **All comments** — every published comment badged **Replied** (your reply
    thread shown inline) or **Needs reply** (with a reply box), filterable by
    Needs reply / Replied / All.

**Security**
- Prepared statements everywhere (SQL-injection safe).
- All output escaped with `htmlspecialchars` (XSS safe).
- **CSRF tokens** on every form and POST handler (`hash_equals`).
- POST-only endpoints reject stray GETs; a wrong/expired token returns `400`.
- Passwords stored with `password_hash`; login **regenerates the session id**
  (anti session-fixation) and slows failed attempts.
- Session + like cookies are **HttpOnly / SameSite=Lax**, and flip to **Secure
  automatically over HTTPS**.
- Free-text input is length-capped; admin image URLs must be real `http(s)` links.
- The SQLite file and secrets live outside the public web folder, with
  deny-all `.htaccess` safety nets in `app/`, `data/`, `db/`, `scripts/` in
  case a server is pointed at the project root by mistake.

### Before you go live
- **Change the admin password** (default `bob` / `changeme`).
- Set `CONTACT_EMAIL` (and confirm the `G123_*` footer links) in `app/config.php`.
- Serve over **HTTPS** (the Secure cookie flag then turns on by itself).
- Set `display_errors = Off` in `php.ini` so errors never reach visitors.
- Set `define('LOAD_SAMPLE_DATA', false);` in `app/config.php` for a clean start.
- Point the web server's document root at **`public/`** (never the project root).
- Consider a CAPTCHA / rate-limit on the public comment & feedback forms if
  spam becomes an issue.

## Project layout

```
public/   ← the ONLY folder the web server exposes (pages + assets)
app/      ← shared PHP kept OUTSIDE the web root (db, admin, views, helpers)
data/     ← the SQLite database file lives here, outside the web root (git-ignored)
db/       ← schema.sql (table blueprint) + seed.sql (sample data)
scripts/  ← install.php (one-time setup)
```

`app/config.php` and `data/*.db` are git-ignored — copy
`app/config.example.php` to `app/config.php` on a new machine and fill in real
values (SMTP for email alerts, etc.).

## Still to build

The last piece not yet wired is **email notifications to Bob** (SMTP settings
already have a home in `app/config.php`). Everything linked in the menus —
feed, question threads, archive, about, "Ask Bob a question", and "Send
feedback" — is built and working.
