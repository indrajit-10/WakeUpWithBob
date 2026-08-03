<?php
require __DIR__ . '/../../app/admin.php';

$pageTitle = 'Admin · ' . SITE_NAME;
$showSearch = false;
$notice = '';
$errors = [];
$editQuestion = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (!empty($_POST['logout'])) {
        admin_logout();
        redirect('/admin/');
    }

    if (!admin_logged_in()) {
        // Reachable before login, so hostile shapes must not crash it.
        $username = clip($_POST['username'] ?? '', 80);
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

        if ($username === '' || $password === '') {
            $errors[] = 'Please enter both your username and password.';
        } elseif (!admin_login($username, $password)) {
            sleep(1);   // small, deliberate delay to slow password guessing
            $errors[] = 'Those admin credentials were not accepted.';
        } else {
            redirect('/admin/');
        }
    } else {
        if (!empty($_POST['delete_question_id'])) {
            delete_question(post_int('delete_question_id'));
            redirect('/admin/?deleted=1');
        } elseif (!empty($_POST['edit_question_id'])) {
            $id       = post_int('edit_question_id');
            $title    = clip($_POST['title'] ?? '', 200);
            $body     = clip($_POST['body'] ?? '', 8000);
            $imageUrl = clean_http_url($_POST['image_url'] ?? '');
            $publishAt = local_input_to_utc($_POST['publish_at'] ?? '');
            if ($body === '') {
                $errors[] = 'Please write the post itself — a title is optional, the text is not.';
                $editQuestion = get_question($id);
            } elseif (($_POST['publish_at'] ?? '') !== '' && $publishAt === null) {
                $errors[] = 'That publish date could not be read — please pick it again.';
                $editQuestion = get_question($id);
            } else {
                // The date field is prefilled to minute precision, so a submit that
                // never touched it would otherwise rewrite created_at and drop the
                // stored seconds — enough to reorder two posts from the same minute
                // and swap their numbers. Only write the date when it really changed.
                $existing = get_question($id);
                if ($existing && $publishAt !== null
                    && utc_to_local_input($existing['created_at']) === utc_to_local_input($publishAt)) {
                    $publishAt = null;   // unchanged — leave created_at exactly as it is
                }
                update_question($id, $title, $body, $imageUrl !== '' ? $imageUrl : null, $publishAt);
                redirect('/admin/?updated=1');
            }
        } else {
            $title    = clip($_POST['title'] ?? '', 200);
            $body     = clip($_POST['body'] ?? '', 8000);
            $imageUrl = clean_http_url($_POST['image_url'] ?? '');
            $publishAt = local_input_to_utc($_POST['publish_at'] ?? '');
            if ($body === '') {
                $errors[] = 'Please write the post itself — a title is optional, the text is not.';
            } elseif (($_POST['publish_at'] ?? '') !== '' && $publishAt === null) {
                $errors[] = 'That publish date could not be read — please pick it again.';
            } else {
                $newId = create_question($title, $body, $imageUrl !== '' ? $imageUrl : null, $publishAt);
                // Tell Bob where it actually went: scheduled, straight to the archive
                // (backdated past the 7-day home window), or live on the feed.
                $where = is_scheduled($publishAt) ? 'scheduled'
                    : (($publishAt !== null && db_time($publishAt) < time() - 7 * 86400) ? 'archived' : '1');
                redirect('/admin/?created=' . $where);
            }
        }
    }
}

// which post are we editing? (GET ?edit=)
if (admin_logged_in() && !$editQuestion && !empty($_GET['edit'])) {
    $editQuestion = get_question((int) $_GET['edit']);
}

// success messages after a redirect (Post/Redirect/Get)
if (!$errors) {
    if (($_GET['created'] ?? '') === 'scheduled') $notice = 'Saved — it will publish by itself at the date and time you set.';
    elseif (($_GET['created'] ?? '') === 'archived') $notice = 'Posted and filed straight into the archive — it is older than the 7-day home feed.';
    elseif (!empty($_GET['created'])) $notice = 'Your new morning post is live on the feed.';
    elseif (!empty($_GET['updated'])) $notice = 'Your changes were saved.';
    elseif (!empty($_GET['deleted'])) $notice = 'The post was deleted from the feed.';
}

// paginated history of ALL posts (10 per page) — only when logged in
$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalPosts = 0;
$totalPages = 1;
$questions = [];
if (admin_logged_in()) {
    $totalPosts = (int) $pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn();
    $totalPages = max(1, (int) ceil($totalPosts / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare(
        'SELECT id, post_number, title, body, created_at, comment_count
         FROM questions ORDER BY COALESCE(post_number, id) DESC, id DESC LIMIT ? OFFSET ?'
    );
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $questions = $stmt->fetchAll();
}

$extraJs = ['/assets/js/editor.js'];   // composer toolbar (bold / italic / underline + emoji)
require __DIR__ . '/../../app/views/header.php';
?>

<?php if (!admin_logged_in()): ?>

  <!-- Logged out: a plain sign-in screen. No sidebar, no counts, nothing leaked. -->
  <div class="admin-login">
    <div class="admin-card admin-login-card">
      <div class="admin-login-brand"><img src="/assets/img/logo.svg" alt="" width="26" height="26"> Admin sign-in</div>
      <p>Sign in to publish and manage Wake up with Bob.</p>
      <?php if ($errors): ?>
        <div class="admin-msg admin-error">
          <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>
      <form class="admin-form" method="post" action="/admin/">
        <?= csrf_field() ?>
        <label><span>Username</span><input type="text" name="username" value="" autocomplete="username" required></label>
        <label><span>Password</span><input type="password" name="password" autocomplete="current-password" required></label>
        <button class="btn-orange" type="submit">Sign in</button>
      </form>
    </div>
  </div>

<?php else: ?>

  <div class="app admin-app">
    <div class="admin-shell">
      <?php include __DIR__ . '/_sidebar.php'; ?>
      <main class="admin-main">
        <div class="admin-header">
          <div>
            <h1>Admin dashboard</h1>
            <p class="muted">Publish, edit and review every morning post.</p>
          </div>
          <form method="post" action="/admin/">
            <?= csrf_field() ?>
            <input type="hidden" name="logout" value="1">
            <button class="pill" type="submit" style="background:var(--red); color: var(--card);line-height: 1; padding-bottom: 13px;">Log out</button>
          </form>
        </div>

        <?php if ($notice !== ''): ?>
          <div class="admin-msg admin-success"><?= e($notice) ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
          <div class="admin-msg admin-error">
            <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
          </div>
        <?php endif; ?>

        <div class="admin-card" id="compose">
          <?php if ($editQuestion): ?>
            <h2>Edit post #<?= (int) $editQuestion['post_number'] ?></h2>
            <p>Update the question below, then save your changes.</p>
            <form class="admin-form" method="post" action="/admin/">
              <?= csrf_field() ?>
              <input type="hidden" name="edit_question_id" value="<?= (int) $editQuestion['id'] ?>">
              <label><span>Title <span class="opt">(optional)</span></span><input type="text" name="title" value="<?= e($editQuestion['title']) ?>"></label>
              <div class="editor-label">
                <label for="edit-body">Post</label>
                <?php $editorTarget = 'edit-body'; include __DIR__ . '/../../app/views/editor-toolbar.php'; ?>
                <textarea id="edit-body" name="body" rows="12" data-editor-field required><?= e($editQuestion['body']) ?></textarea>
              </div>
              <label><span>Image URL (optional)</span><input type="url" name="image_url" value="<?= e($editQuestion['image_url'] ?? '') ?>"></label>
              <label><span>Publish date &amp; time</span>
                <input type="datetime-local" name="publish_at" value="<?= e(utc_to_local_input($editQuestion['created_at'])) ?>">
              </label>
              <p class="muted tiny pub-hint">A past date files it straight into the archive; a future date holds it back until then. Times are <?= e(defined('APP_TIMEZONE') ? APP_TIMEZONE : 'UTC') ?>.</p>
              <div class="admin-form-actions">
                <button class="btn-orange" type="submit">Save changes</button>
                <a class="pill" href="/admin/">Cancel</a>
              </div>
            </form>
          <?php else: ?>
            <h2>Post to the feed</h2>
            <p>Create a new morning question that appears on the public home page.</p>
            <form class="admin-form" method="post" action="/admin/">
              <?= csrf_field() ?>
              <label><span>Title <span class="opt">(optional)</span></span><input type="text" name="title" placeholder="Leave blank to post without a heading"></label>
              <div class="editor-label">
                <label for="post-body">Post</label>
                <?php $editorTarget = 'post-body'; include __DIR__ . '/../../app/views/editor-toolbar.php'; ?>
                <textarea id="post-body" name="body" rows="12" data-editor-field placeholder="Write this morning's post. Your line breaks are kept exactly as you type them." required></textarea>
              </div>
              <label><span>Image URL (optional)</span><input type="url" name="image_url" placeholder="https://example.com/photo.jpg"></label>
              <label><span>Publish date &amp; time <span class="opt">(optional)</span></span>
                <input type="datetime-local" name="publish_at" value="">
              </label>
              <p class="muted tiny pub-hint">Leave blank to publish now. A <b>past</b> date backdates it into the archive; a <b>future</b> date schedules it — it appears by itself, no cron needed. Times are <?= e(defined('APP_TIMEZONE') ? APP_TIMEZONE : 'UTC') ?>.</p>
              <button class="btn-orange" type="submit">Publish to feed</button>
            </form>
          <?php endif; ?>
        </div>

        <div class="admin-card">
          <div class="admin-card-head">
            <h2>All posts</h2>
            <span class="muted"><?= (int) $totalPosts ?> total · newest first</span>
          </div>
          <?php if ($questions): ?>
            <ul class="post-history">
              <?php foreach ($questions as $q): ?>
                <li>
                  <span class="post-num">#<?= (int) $q['post_number'] ?></span>
                  <div class="post-hist-main">
                    <a class="post-hist-title" href="/question.php?id=<?= (int) $q['id'] ?>"><?= e(post_label($q['title'], $q['body'], $q['post_number'] ?? null)) ?></a>
                    <div class="post-hist-meta">
                      <?= e(fmt_datetime($q['created_at'])) ?> · <?= (int) $q['comment_count'] ?> comments
                      <?php if (is_scheduled($q['created_at'])): ?>
                        <span class="badge-sched">Scheduled</span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="post-hist-actions">
                    <a class="pill" href="/admin/?edit=<?= (int) $q['id'] ?>#compose">Edit</a>
                    <form method="post" action="/admin/" data-confirm="Delete post #<?= (int) $q['post_number'] ?>?">
                      <?= csrf_field() ?>
                      <input type="hidden" name="delete_question_id" value="<?= (int) $q['id'] ?>">
                      <button class="admin-delete" type="submit">Delete</button>
                    </form>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>

            <?php if ($totalPages > 1): ?>
              <nav class="pagination">
                <a class="page-link<?= $page <= 1 ? ' disabled' : '' ?>" href="/admin/?page=<?= max(1, $page - 1) ?>">← Prev</a>
                <span class="page-info">Page <?= (int) $page ?> of <?= (int) $totalPages ?></span>
                <a class="page-link<?= $page >= $totalPages ? ' disabled' : '' ?>" href="/admin/?page=<?= min($totalPages, $page + 1) ?>">Next →</a>
              </nav>
            <?php endif; ?>
          <?php else: ?>
            <p class="muted">No posts yet.</p>
          <?php endif; ?>
        </div>

      </main>
    </div>
  </div>

<?php endif; ?>
<?php require __DIR__ . '/../../app/views/footer.php'; ?>
