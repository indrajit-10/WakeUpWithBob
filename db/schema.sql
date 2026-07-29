-- Wake up with Bob — database schema (SQLite)
-- Safe to run repeatedly: every table uses "IF NOT EXISTS".

-- One question per morning, authored by the admin (Bob).
CREATE TABLE IF NOT EXISTS questions (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  title         TEXT    NOT NULL,
  body          TEXT    NOT NULL,
  image_url     TEXT,                                   -- optional admin image
  created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
  comment_count INTEGER NOT NULL DEFAULT 0,             -- approved comments only
  post_number   INTEGER                                 -- display label: 1..N in DATE order, RESEQUENCED on
                                                        -- create/date-edit/delete. Not stable — never use as a
                                                        -- permalink or a "first ever post" pointer; use id.
);

-- Public replies. Every one starts pending until Bob approves it.
CREATE TABLE IF NOT EXISTS comments (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  question_id    INTEGER NOT NULL REFERENCES questions(id) ON DELETE CASCADE,
  parent_id      INTEGER REFERENCES comments(id) ON DELETE CASCADE,  -- for Bob's threaded replies
  author_name    TEXT,                                  -- free text; no account behind it
  body           TEXT    NOT NULL,
  media_url      TEXT,                                  -- pasted image/gif link (phase 1)
  is_admin_reply INTEGER NOT NULL DEFAULT 0,            -- 1 = Bob's own reply
  approved       INTEGER NOT NULL DEFAULT 0,            -- 0 = pending, 1 = visible
  reply_count    INTEGER NOT NULL DEFAULT 0,
  created_at     TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- Admin login(s).
CREATE TABLE IF NOT EXISTS admins (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  username      TEXT    NOT NULL UNIQUE,
  password_hash TEXT    NOT NULL,
  created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- "Ask Bob a question" submissions from the right rail. Private — never shown publicly.
CREATE TABLE IF NOT EXISTS reader_questions (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  body       TEXT    NOT NULL,
  email      TEXT,
  status     TEXT    NOT NULL DEFAULT 'new',            -- new | used | dismissed
  created_at TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- Feedback submissions from the right rail.
CREATE TABLE IF NOT EXISTS feedback (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  body       TEXT    NOT NULL,
  email      TEXT,
  status     TEXT    NOT NULL DEFAULT 'new',            -- new | done
  created_at TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- Anonymous search log: what people searched + how many results came back.
-- No IP, no session id — not linked to any person.
CREATE TABLE IF NOT EXISTS searches (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  query        TEXT    NOT NULL,
  result_count INTEGER NOT NULL DEFAULT 0,
  created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- Admin-editable settings (simple key/value). e.g. 'alert_email' — the inbox
-- that receives new-comment / question / feedback alerts, changeable from the
-- admin Settings page without touching env vars or redeploying.
CREATE TABLE IF NOT EXISTS settings (
  key   TEXT PRIMARY KEY,
  value TEXT NOT NULL DEFAULT ''
);

CREATE INDEX IF NOT EXISTS idx_questions_created   ON questions (created_at);
CREATE INDEX IF NOT EXISTS idx_questions_number    ON questions (post_number);
CREATE INDEX IF NOT EXISTS idx_comments_q_approved ON comments (question_id, approved);
CREATE INDEX IF NOT EXISTS idx_searches_created    ON searches (created_at);
