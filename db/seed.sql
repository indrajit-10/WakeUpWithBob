-- Sample data for local development so the feed isn't empty.
-- (Real deployments start empty and fill up on their own.)

INSERT INTO questions (id, title, body, created_at, comment_count, post_number) VALUES
 (212, 'What did you have for breakfast — and did you actually taste it?',
  'Standing over the sink counts. So does nothing at all — no judgment. Just curious whether it registered, or vanished on the way to something more urgent.',
  '2026-07-07 06:30:00', 1, 1),
 (213, 'Is there a small ritual that makes the morning feel like yours?',
  'A pour-over you refuse to rush. Ten minutes on the step. The same three songs, every day. What''s the little thing you protect before the world starts asking for something?',
  '2026-07-08 06:30:00', 3, 2),
 (214, 'What''s the first thing you did after waking up today?',
  'Not the version you''d put on a postcard — the real one. The reach for the phone, the pull of the blanket, the cracked window, the dog at the door. A little honesty before the day gets loud.',
  '2026-07-09 06:30:00', 7, 3);

-- A full threaded conversation (explicit ids so parents/replies line up).
INSERT INTO comments (id, question_id, parent_id, author_name, body, is_admin_reply, approved, reply_count, created_at) VALUES
 -- Q214: four top-level comments + a nested thread (reader → Bob → Dana) and a reply
 (1, 214, NULL, 'a reader', 'Opened the window before anything else. Cold air first, coffee second. Feels like signing for the day.', 0, 1, 1, '2026-07-09 07:14:00'),
 (2, 214, NULL, 'a reader', 'Checked my phone. I''m not proud of it, but you did ask for the real one.', 0, 1, 1, '2026-07-09 06:52:00'),
 (3, 214, NULL, 'a reader', 'Let the dog out and stood in the wet grass barefoot for a minute. Ten seconds of nothing before everything.', 0, 1, 0, '2026-07-09 06:38:00'),
 (4, 214, NULL, 'a reader', 'Snoozed twice, then a panic shower. Not my finest work, but I''m here.', 0, 1, 0, '2026-07-09 06:20:00'),
 (5, 214, 1, 'Bob', 'That order matters more than people think — air, then caffeine. And I''m stealing "signing for the day."', 1, 1, 1, '2026-07-09 07:20:00'),
 (6, 214, 2, 'a reader', 'Same. I keep telling myself I''ll stop. Tomorrow, probably.', 0, 1, 0, '2026-07-09 07:02:00'),
 (7, 214, 5, 'Dana', 'Ha — I do the exact opposite. Caffeine first, then I can face the cold.', 0, 1, 0, '2026-07-09 07:32:00'),
 -- Q213
 (8, 213, NULL, 'a reader', 'Coffee, then absolutely nothing for fifteen minutes. Non-negotiable — the only part of the day nobody else owns.', 0, 1, 1, '2026-07-08 06:48:00'),
 (9, 213, 8, 'Bob', 'Protecting fifteen minutes of nothing is harder than it sounds. Respect.', 1, 1, 0, '2026-07-08 07:05:00'),
 (10, 213, NULL, 'a reader', 'Ten minutes stretching by the window before I let the day in.', 0, 1, 0, '2026-07-08 06:30:00'),
 -- Q212
 (11, 212, NULL, 'a reader', 'Toast standing up, half-read news on my phone. Didn''t taste a single bite, if I''m being honest.', 0, 1, 0, '2026-07-07 08:03:00');

-- Sample "Ask Bob" questions and feedback so the admin inboxes aren't empty.
INSERT INTO reader_questions (body, email, status, created_at) VALUES
 ('What''s a song that instantly puts you in a good mood in the morning?', 'jess@example.com', 'new', '2026-07-09 08:02:00'),
 ('Do you make your bed as soon as you''re up, or leave it?', NULL, 'new', '2026-07-09 07:36:00');

INSERT INTO feedback (body, email, status, created_at) VALUES
 ('Love this. Could the archive let me jump to a specific date? Would make finding old mornings easier.', 'sam@example.com', 'new', '2026-07-09 06:59:00'),
 ('Dark mode looks great on my phone — thank you!', NULL, 'new', '2026-07-08 22:10:00');

-- Sample search log so the "User Search Data" report isn't empty in dev.
INSERT INTO searches (query, result_count, created_at) VALUES
 ('coffee', 0, '2026-07-09 07:20:00'),
 ('morning ritual', 2, '2026-07-09 09:15:00'),
 ('9 July', 1, '2026-07-09 12:41:00'),
 ('5 July', 0, '2026-07-08 11:02:00'),
 ('breakfast', 1, '2026-07-08 18:44:00'),
 ('postcard', 1, '2026-07-07 08:30:00');
