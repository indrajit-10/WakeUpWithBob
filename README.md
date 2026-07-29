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
deployment with no sample data, set `LOAD_SAMPLE_DATA=false` — see
[Going live](#going-live--the-complete-checklist).)*

**Admin:** go to **http://localhost:8000/admin/** and log in with
**username `bob` / password `changeme`**.

> Change it before anyone else can reach the site:
> `php scripts/set_password.php bob 'a long pass phrase'`

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

> **Deploying for real? Follow [Going live — the complete checklist](#going-live--the-complete-checklist)
> below.** It lists every setting, exactly where the API key goes, and how to
> change the admin password (the site ships with a default one).

## Going live — the complete checklist

Work top to bottom. Nothing here is optional except where it says so.

### 1. Where settings go (read this first)

Every setting lives in **`app/config.php`**, and **every one of them can also be
supplied as an environment variable, which always wins.** So you have two ways to
configure the site:

| | Use it when | How |
|---|---|---|
| **Environment variables** *(recommended for a live server)* | Any container host — Render, Fly, Docker | Set them in the host's dashboard. Nothing to edit, nothing to rebuild, and **secrets stay out of your files.** |
| **Editing `app/config.php`** | Local development, or a plain VPS you control | Copy `app/config.example.php` → `app/config.php` and change the fallback values. |

> `app/config.php` is **git-ignored**, so real values are never committed. On a
> Docker/Render build it is created automatically from `app/config.example.php`,
> which is exactly why the env vars matter there — see step 3.

### 2. Where the API key goes

The Resend API key is a **secret**. It belongs in one of these two places and
nowhere else — never in a template, never committed, and it is never shown in the
admin UI.

**On a live server (recommended)** — set an environment variable:

```
RESEND_API_KEY = re_xxxxxxxxxxxxxxxx
```

On Render: *Dashboard → your service → **Environment** → Add Environment Variable*.

**Running locally / on your own VPS** — open **`app/config.php`** and put it in the
fallback slot on this line (near the bottom of the file):

```php
define('RESEND_API_KEY', getenv('RESEND_API_KEY') ?: 're_xxxxxxxxxxxxxxxx');
//                                                    ^^^^^^^^^^^^^^^^^^^^ here
```

Then turn sending on, or alerts only get written to `data/mail.log`:

```php
define('MAIL_ENABLED', (getenv('MAIL_ENABLED') ?: 'true') === 'true');
//                                                ^^^^^^ 'false' → 'true'
```

### 3. Every setting, and what to put in it

Set at minimum everything marked **required**.

| Setting | Required? | What it does | Example |
|---|---|---|---|
| `SITE_URL` | **yes** | Your real public address, **no trailing slash**. Links inside alert emails are built from it — leave it as `localhost` and every link you receive points at your own machine. | `https://bob.example.com` |
| `SITE_NAME` | no | Name in the browser tab and emails | `Wake up with Bob` |
| `APP_TIMEZONE` | **yes** | The zone dates are shown, grouped and searched in ([list](https://www.php.net/timezones)) | `Asia/Kolkata` |
| `CONTACT_EMAIL` | **yes** | Shown on the Contact page | `hello@example.com` |
| `LOAD_SAMPLE_DATA` | **yes** | `false` **before the first run** for a clean site — see step 5 | `false` |
| `MAIL_ENABLED` | for email | `true` sends real email; anything else logs to `data/mail.log` | `true` |
| `RESEND_API_KEY` | for email | Your key from Resend (step 2) | `re_xxxx…` |
| `ADMIN_EMAIL` | for email | Inbox that receives every alert | `you@yourdomain.com` |
| `MAIL_FROM` | for email | Verified sender, `Name <address>` | `Wake up with Bob <alerts@yourdomain.com>` |
| `APP_DEBUG` | no | Leave unset. `true` shows full errors on screen — **never on a live site** | *(unset)* |
| `ADMIN_IDLE_TIMEOUT` | no | Seconds before an idle admin is signed out (default 1800 = 30 min) | `1800` |
| `G123_HOME` / `G123_ECARDS` / `G123_BLOG` / `G123_LOGO` | no | Footer links and logo | *(defaults are the real 123Greetings site)* |
| `DB_PATH` | no | Where the SQLite file lives. Change it **only** to point at a persistent disk | `/var/data/mornings.db` |

### 4. Change the admin password — do not skip this

The site ships with **`bob` / `changeme`**. Anyone who knows that owns your admin.
There is a CLI tool for it:

```bash
php scripts/set_password.php bob 'a long pass phrase you will remember'
```

It stores a `password_hash()` digest (never plain text), needs at least 8
characters, and creates the account if it doesn't exist yet — so you can also add
a second admin:

```bash
php scripts/set_password.php editor 'another good pass phrase'
```

On a container host, run it from the running instance's shell (on Render:
*Dashboard → your service → **Shell***).

> **On an ephemeral disk the password resets to `changeme` on every deploy**,
> because the whole database is rebuilt. If your site is public, attach a
> persistent disk (step 6) — otherwise you must redo this after each deploy.

### 5. Start with a clean site

On its very first run the database seeds a few **sample posts** so nothing looks
broken while you explore. For a real site you want none of that:

Set **`LOAD_SAMPLE_DATA=false` before the first page load.** If the database has
already been created with sample data, delete `data/mornings.db` and let it
rebuild.

If you skip this, every sample post is already more than 7 days old, so the home
feed keeps showing one of them through the 7-day fallback until you publish real
writing.

### 6. Storage — the part people get wrong

The whole site (posts, comments, admin password, settings) lives in one SQLite
file at `data/mornings.db`.

- **Render's free tier has an ephemeral disk.** Every deploy — and every restart —
  wipes it and rebuilds from `db/schema.sql`. Your posts, comments and password
  are lost. Fine for a demo, **not for a real site**.
- For anything real, **attach a persistent disk** mounted at `/var/www/html/data`
  (or point `DB_PATH` at wherever you mounted it), or use a host with a normal
  filesystem.

### 7. Web server

- Point the document root at **`public/`** — never the project root. Everything
  sensitive (the database, `app/`, `db/`, `scripts/`) sits outside it, with
  deny-all `.htaccess` files as a second line of defence.
- Make **`data/`** and **`public/uploads/`** writable by the web-server user.
  The bundled `Dockerfile` already does both.
- Serve over **HTTPS**. The session cookie's `Secure` flag then turns itself on.

### 8. Turn on email alerts (optional but recommended)

1. Sign up at [resend.com](https://resend.com) and create an **API key**.
2. Verify a sending domain. *(For a quick first test you can use Resend's
   `onboarding@resend.dev` as `MAIL_FROM` — it only delivers to the address you
   signed up with.)*
3. Set `RESEND_API_KEY`, `MAIL_ENABLED=true`, `ADMIN_EMAIL`, `MAIL_FROM` and a
   real `SITE_URL` (steps 2–3).
4. Sign in to the admin → **Settings** → **Send a test alert**.

The Settings page shows whether you're **Live** or in **Log mode**, and lets you
change the recipient address later without touching config or redeploying.

### 9. Final check before you announce it

- [ ] Visit the site over **https://** — no certificate warning
- [ ] `/admin/` — **old password no longer works**, new one does
- [ ] Publish a test post → it appears on the home feed
- [ ] Comment on it as a visitor → it does **not** appear publicly yet
- [ ] Admin → **Moderation** → approve it → now it appears
- [ ] You received the **alert email** (or it's in `data/mail.log` if mail is off)
- [ ] Dates on posts match your local clock (`APP_TIMEZONE` is right)
- [ ] Trigger an error path (e.g. `/question.php?id=999999`) → a plain message,
      **no stack trace or file paths** (`APP_DEBUG` must be unset)
- [ ] Delete your test post and test comment

### Nice to have later

- A **CAPTCHA or rate-limit** on the public comment / feedback forms if spam starts.
- Regular **backups** of `data/mornings.db` — it is the entire site. On a
  persistent disk, a scheduled copy of that one file is a complete backup.

## What shows on the home feed (and starting clean)

The home page (`/`) lists only mornings from the **last 7 days** — older ones
move to the archive. If a whole week goes by with **no new post**, home does not
go blank: it falls back to the single most-recent morning so visitors always land
on something. So a lone, weeks-old post on an otherwise-quiet home page is that
fallback — **not a bug**.

**Starting a real site clean.** On the very first run the database seeds a few
**sample posts** (dated in the past) so nothing is empty while you explore. For a
live site you almost certainly want to start empty — set
**`LOAD_SAMPLE_DATA=false`** (env var, or the fallback in `app/config.php`)
**before** the database is first built. If you leave sample data on, every sample post is already more
than 7 days old, so the home feed will keep showing one of them (via the fallback
above) until you publish real questions.

> **Ephemeral hosts (e.g. Render's free tier):** the database rebuilds from
> `schema.sql` + `seed.sql` on every deploy, so sample data reappears and any real
> content you added is reset each time. For a durable live site, run with
> `LOAD_SAMPLE_DATA=false` **and** attach a persistent disk at `/var/www/html/data`
> (or use a paid instance) so posts survive deploys.

## What's included

**Public**
- **Feed (`/`)** — the **last 7 days** of mornings (older ones move to the
  archive; if a whole week is empty it falls back to the latest post — see
  *"What shows on the home feed"* above). Two views: **Latest** (newest first)
  and **Most Engaging** (most commented). Each post shows its **date** and its
  top comment.
- **Search** (top bar) — searches the **title and body** of every morning,
  all-time, and is also **date-aware**. See [Search](#search) below.
- **Question page (`/question.php?id=…`)** — the full question with a share bar,
  a fully threaded comment tree (reply to any comment or reply), and a comment
  form. Comments are held for approval — right after you post you see a
  **dismissible pop-up copy of your own comment** marked *"waiting for Bob to
  read it"* (only you see it, only until Bob approves).
- **Archive (`/archive.php`)** — a browsable index of every past morning,
  **one month at a time**: a month/year jump menu plus prev/next-month arrows,
  with each morning's number and date.
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
- Publish, **edit**, and delete morning posts. The **title is optional** — leave
  it blank and the post appears as just the writing, with no heading (list views
  like the archive then label it with a short excerpt). Line breaks and blank
  lines are kept exactly as typed, and emoji are supported.
- **Composer toolbar** — select text and tap **B** / *I* / <u>U</u> (or press
  Ctrl/Cmd+B, +I, +U) to wrap it, plus a searchable **emoji picker**. The box stays
  a plain textarea: the buttons just insert the markers below, so what you write is
  what readers see. Tapping a button again unwraps; selecting several lines wraps
  each line. Each post carries a **number** (`#1` = the oldest morning, counting up
  by date — see *Backdating and scheduling*), with a paginated history.
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

## Backdating and scheduling

Every post has one **Publish date & time** field, and it does both jobs:

- **Leave it blank** → publishes now.
- **A past date** → the post is *backdated*: it goes straight into the archive
  under that month. Handy for filling in a backlog of older writing. (Anything
  older than 7 days simply isn't on the home feed — see the home-feed section.)
- **A future date** → the post is *scheduled*: it stays completely hidden until
  that moment, then appears by itself. **No cron job or worker is needed** —
  "published" just means the date has passed, so the post goes live on the first
  page load after its time.

Times are entered in **`APP_TIMEZONE`** and stored as UTC, so an early-morning
time can't drift onto the wrong day. Dates are also *grouped and searched* in
`APP_TIMEZONE`, so the date a reader sees on a post is the same date it is filed
and found under — a post at 01:30 IST is "10 April" everywhere, even though UTC
calls it 9 April. For any new date grouping or filtering use
`local_month_utc_range()` / `local_day_utc_range()` (they resolve each date's own
offset, so they stay correct across DST) rather than shifting the column. Scheduled posts are listed in the admin with
a **Scheduled** badge; only you can see them.

**Post numbers stay chronological.** `#1` is always your oldest morning, so
backfilling old writing renumbers the sequence rather than tacking old posts onto
the end. (Numbers are display labels — permalinks use the post id and never change.)

> A scheduled post is hidden *everywhere* public — feed, "Most Engaging", search,
> the archive and its month list, the About counters — and its direct URL returns
> **404** until it publishes. Commenting on one is refused. Every public query is
> gated by `published_sql()` in `app/helpers.php`; use it in any new public query.

## Writing a post (formatting)

Post bodies are plain text with a little Markdown-style emphasis. Line breaks and
blank lines are kept exactly as typed, and emoji work anywhere.

| You type | Readers see |
|---|---|
| `**bold**` or `__bold__` | **bold** |
| `*italic*` or `_italic_` | *italic* |
| `++underline++` | <u>underline</u> |

The admin composer's **B / I / U** buttons (and Ctrl/Cmd+B, +I, +U) insert these
markers around your selection, so you never have to type them by hand.

Two rules, both handled for you by the toolbar: markers must **hug** their text
(`**like this**`, not `** like this **`), and a marker pair cannot span a line
break — so selecting a whole paragraph wraps each line. This is deliberate: it
means ordinary prose, arithmetic like `3 * 4`, and `snake_case` names are never
mistaken for formatting.

> **Security note.** Post text is HTML-escaped *first*, and only then are the
> `<strong>` / `<em>` / `<u>` tags introduced by fixed rules in
> `format_post_text()`. Author input can never contribute a tag or attribute, so
> pasted HTML (or a `<script>`) is displayed as literal text, never executed.
> Underline is not standard Markdown; `++…++` is this app's own syntax.

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
- **Matches are highlighted** in the result cards (title and body). Highlighting is
  applied to the raw text *before* escaping and only becomes `<mark>` afterwards, so
  searching for `strong`, `quot` or `<script>` can never corrupt a tag or an entity —
  a search term cannot introduce markup. The no-match fallback post isn't highlighted,
  since it isn't a match.
- If a search matches nothing, the feed shows the **all-time most-commented**
  morning under a friendly note instead of an empty page.

Every non-empty search is logged **anonymously** (the query, the result count,
and a timestamp — no name, no cookie tie-in) for the admin **User Search Data**
report. *(Because search is a `GET` request, the query — like on most sites —
also appears in ordinary web-server access logs.)*

## Email alerts

Bob can get an **email the moment a reader submits something** — one email per
submission — so nothing sits in an inbox unseen. Alerts fire on:

- a **new comment** (links straight to moderation),
- a **new "Ask Bob a question"** suggestion,
- **new feedback** and **Contact** messages.

Every alert goes to a single address (`ADMIN_EMAIL`) and links to the right
admin inbox. Reader-supplied text is HTML-escaped in the email, and sending is
**best-effort**: it never blocks or breaks a submission — if the mail provider
is slow or down, the reader's comment/question still saves and the failure is
just logged.

**How it sends.** Alerts use [Resend](https://resend.com), a transactional
email API called over plain HTTPS (`curl`) — **no libraries, no SMTP, no
Composer**.

> **Setting it up (API key, sender, recipient) is step 2 and step 8 of
> [Going live](#going-live--the-complete-checklist).** That's the single place
> every setting is listed; this section just explains how the feature behaves.

You can change the recipient address any time from the admin **Settings** page —
no redeploy, no config edit. That page also shows whether sending is **Live** or
in **Log mode**, and has a **Send a test alert** button.

**Local development / mail off.** When `MAIL_ENABLED` is not `true` (or the key
is blank, or `curl` isn't available), alerts are **written to `data/mail.log`**
instead of sent — so you can see exactly what *would* have gone out without
wiring up a provider. On Render's free tier `data/` is ephemeral, so that log
(like the database) resets on each deploy.

## Security

**Baseline**
- **Prepared statements everywhere** (SQL-injection safe) — including the
  dynamically-built search query, whose every value is bound, never concatenated.
- **All output escaped** with `htmlspecialchars` (`e()`) — XSS safe.
- **CSRF tokens** on every form and POST handler (`hash_equals`). POST-only
  endpoints reject stray GETs; a wrong/expired token returns `400`.
- Admin pages are gated on a hashed-password login; passwords use
  `password_hash`, login **regenerates the session id** (anti session-fixation)
  and slows failed attempts. An admin session **auto signs-out after 30 minutes
  of inactivity** (set `ADMIN_IDLE_TIMEOUT`, in seconds, to change it), so an
  unattended session doesn't stay open indefinitely.
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

See **[Going live — the complete checklist](#going-live--the-complete-checklist)** above:
it covers the admin password, where the API key goes, storage, HTTPS and a final
pre-launch check.

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
scripts/        ← install.php (setup), set_password.php (change an admin
                  password), test_admin.php
Dockerfile      ← container build for Render / any Docker host
```

`app/config.php` and `data/*.db` are git-ignored — copy
`app/config.example.php` to `app/config.php` on a new machine and fill in real
values.

## What's built

Everything in the menus is working — feed, question threads, search, archive,
about, "Ask Bob a question", "Send feedback" / Contact — plus admin moderation,
the User Search Data report, and **email alerts** (see above) on every reader
submission. Nothing is left stubbed out.

Nice-to-haves you might add later: a CAPTCHA / rate-limit on the public forms if
spam appears, and a persistent disk (or paid instance) if you outgrow the free
tier's ephemeral database.
