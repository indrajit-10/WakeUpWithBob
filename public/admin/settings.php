<?php
/**
 * Admin · Settings  (  /admin/settings.php  )
 * Edit the maroon sidebar buttons (text + link), and change the inbox that
 * receives alert emails (new comments / questions / feedback) without editing
 * config or redeploying. The API key and the on/off
 * switch stay in the server's config/env (secrets don't belong in the database);
 * this page shows their current state read-only and lets Bob send a test alert.
 */
require __DIR__ . '/../../app/admin.php';
require __DIR__ . '/../../app/ctas.php';

if (!admin_logged_in()) {
    redirect('/admin/');
}

// Is real sending wired up, or are we in log-to-file mode?
$mailEnabled = defined('MAIL_ENABLED') && MAIL_ENABLED;
$hasKey      = defined('RESEND_API_KEY') && RESEND_API_KEY !== '';
$hasCurl     = function_exists('curl_init');
$mailLive    = $mailEnabled && $hasKey && $hasCurl;

$notice = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (isset($_POST['save_email'])) {
        $email = clip($_POST['alert_email'] ?? '', 120);
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'That does not look like a valid email address.';
        } else {
            setting_set('alert_email', $email);   // empty clears the override → falls back to ADMIN_EMAIL
            redirect('/admin/settings.php?saved=1');
        }
    } elseif (isset($_POST['save_ctas'])) {
        // Sidebar buttons. A link must be a full http(s) address; an empty text or
        // link (or "Show" unticked) simply hides that button on the site.
        $ctaPosted = [];
        foreach (array_keys(sidebar_cta_defaults()) as $n) {
            $raw = $_POST["cta{$n}_url"] ?? '';
            $raw = is_string($raw) ? trim($raw) : '';
            $url = clean_http_url($raw);
            if (mb_strlen($raw) > 2000) {          // refused rather than cut short: the end may be tracking tags
                $errors[] = "Button {$n}: that link is too long (2000 characters at most).";
                $url = '';
            } elseif ($raw !== '' && $url === '') {
                $errors[] = "Button {$n}: the link must be a full web address starting with https://";
            }
            $icon = $_POST["cta{$n}_icon"] ?? '';
            $ctaPosted[$n] = [
                'icon' => is_string($icon) && array_key_exists($icon, sidebar_cta_icons()) ? $icon : '',
                'text' => clip($_POST["cta{$n}_text"] ?? '', 40),
                'url'  => $url !== '' ? $url : $raw,
                'on'   => !empty($_POST["cta{$n}_on"]),
            ];
        }
        if (!$errors) {
            foreach ($ctaPosted as $n => $c) {
                setting_set("cta{$n}_text", $c['text']);
                setting_set("cta{$n}_url", $c['url']);
                setting_set("cta{$n}_icon", $c['icon']);
                setting_set("cta{$n}_on", $c['on'] ? '1' : '0');
            }
            redirect('/admin/settings.php?ctas=1');   // no #anchor: the message at the top must stay visible
        }
    } elseif (isset($_POST['send_test'])) {
        $to = alert_recipient();
        if ($to === '') {
            $errors[] = 'Set an alert email (or ADMIN_EMAIL in config) before sending a test.';
        } else {
            $ok = send_admin_alert(
                'Test alert · ' . SITE_NAME,
                '<p>This is a <strong>test alert</strong> from your ' . e(SITE_NAME) . ' admin settings.</p>'
                . '<p>If you received this by email, alerts are working.</p>',
                "This is a test alert from your " . SITE_NAME . " admin settings.\n"
                . "If you received this by email, alerts are working."
            );
            if ($mailLive) {
                redirect($ok ? '/admin/settings.php?test=sent' : '/admin/settings.php?test=failed');
            }
            redirect('/admin/settings.php?test=logged');   // log-mode: written to data/mail.log
        }
    }
}

// Post/Redirect/Get success + test messages
if (!$errors) {
    if (!empty($_GET['saved']))            $notice = 'Saved. New alerts will go to the address below.';
    elseif (!empty($_GET['ctas']))         $notice = 'Saved. The sidebar buttons on the site are updated.';
    elseif (($_GET['test'] ?? '') === 'sent')   $notice = 'Test alert sent — check the inbox.';
    elseif (($_GET['test'] ?? '') === 'logged') $notice = 'Mail is in log mode, so the test was written to data/mail.log (not emailed).';
    elseif (($_GET['test'] ?? '') === 'failed') $errors[] = 'The test could not be sent. Check the server error log and your Resend key.';
}

// The sidebar-buttons form shows what was just typed if saving failed, else what is saved.
$ctaForm = $ctaPosted ?? sidebar_ctas_all();

$currentEmail = (string) setting_get('alert_email', '');
$effective    = alert_recipient();
$configEmail  = defined('ADMIN_EMAIL') ? (string) ADMIN_EMAIL : '';
$mailFrom     = defined('MAIL_FROM') ? (string) MAIL_FROM : '';

$pageTitle  = 'Settings · ' . SITE_NAME;
$showSearch = false;
require __DIR__ . '/../../app/views/header.php';
?>
<div class="app admin-app">
  <div class="admin-shell">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <main class="admin-main">
      <div class="admin-header">
        <div>
          <h1>Settings</h1>
          <p class="muted">The sidebar buttons, and where email alerts are sent when a reader comments, asks a question, or sends feedback.</p>
        </div>
      </div>

      <?php if ($notice !== ''): ?>
        <div class="admin-msg admin-success"><?= e($notice) ?></div>
      <?php endif; ?>
      <?php if ($errors): ?>
        <div class="admin-msg admin-error">
          <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <div class="admin-card" id="sidebar-buttons">
        <h2>Sidebar buttons</h2>
        <p class="muted">The maroon buttons at the top of the right-hand column, and of the menu on phones and tablets. They open in a new tab. Untick “Show”, or leave the text or link empty, to hide a button.</p>
        <form class="admin-form" method="post" action="/admin/settings.php">
          <?= csrf_field() ?>
          <?php foreach ($ctaForm as $n => $c): ?>
            <fieldset class="cta-edit">
              <legend>Button <?= (int) $n ?></legend>
              <label class="cta-show"><input type="checkbox" name="cta<?= (int) $n ?>_on" value="1"<?= $c['on'] ? ' checked' : '' ?>> Show this button</label>
              <label>
                <span>Button text</span>
                <input type="text" name="cta<?= (int) $n ?>_text" value="<?= e($c['text']) ?>" maxlength="40">
              </label>
              <label>
                <span>Link</span>
                <input type="url" name="cta<?= (int) $n ?>_url" value="<?= e($c['url']) ?>" maxlength="2000" placeholder="https://www.123greetings.com/…">
              </label>
              <label>
                <span>Icon</span>
                <select name="cta<?= (int) $n ?>_icon">
                  <?php foreach (sidebar_cta_icons() as $key => $icon): ?>
                    <option value="<?= e($key) ?>"<?= $key === $c['icon'] ? ' selected' : '' ?>><?= e($icon['label']) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <?php if (trim($c['text']) !== ''): /* how it looks with what is saved now */ ?>
                <div class="cta-preview"><span class="muted tiny">Preview</span><span class="btn-orange btn-maroon"><?= sidebar_cta_icon_svg($c['icon']) ?><?= e(trim($c['text'])) ?></span></div>
              <?php endif; ?>
            </fieldset>
          <?php endforeach; ?>
          <div class="admin-form-actions">
            <button class="btn-orange" type="submit" name="save_ctas" value="1">Save buttons</button>
          </div>
        </form>
      </div>

      <div class="admin-card">
        <h2>Alert email</h2>
        <p class="muted">Every new comment, reader question, feedback and contact message is emailed here — one email per submission.</p>
        <form class="admin-form" method="post" action="/admin/settings.php">
          <?= csrf_field() ?>
          <label>
            <span>Send alerts to</span>
            <input type="email" name="alert_email" value="<?= e($currentEmail) ?>"
                   placeholder="<?= e($configEmail !== '' ? $configEmail : 'you@yourdomain.com') ?>" maxlength="120">
          </label>
          <p class="muted tiny">
            <?php if ($currentEmail === '' && $configEmail !== ''): ?>
              Leave blank to use the address from your server config (<strong><?= e($configEmail) ?></strong>).
            <?php else: ?>
              Leave blank to fall back to the address in your server config.
            <?php endif; ?>
            Alerts currently go to <strong><?= e($effective !== '' ? $effective : 'nowhere — none set') ?></strong>.
          </p>
          <div class="admin-form-actions">
            <button class="btn-orange" type="submit" name="save_email" value="1">Save</button>
          </div>
        </form>
      </div>

      <div class="admin-card">
        <h2>Sending status</h2>
        <?php if ($mailLive): ?>
          <p class="admin-status-live">● Live — alerts are emailed through Resend.</p>
        <?php else: ?>
          <p class="admin-status-off">● Log mode — alerts are written to <code>data/mail.log</code>, not emailed.</p>
          <p class="muted tiny">
            To send real emails, set these on your server (environment variables, or in
            <code>app/config.php</code>):
            <?php if (!$mailEnabled): ?><code>MAIL_ENABLED=true</code><?php endif; ?>
            <?php if (!$hasKey): ?> <code>RESEND_API_KEY=…</code><?php endif; ?>
            <?php if (!$hasCurl): ?> (PHP <code>curl</code> extension is not available here)<?php endif; ?>.
          </p>
        <?php endif; ?>
        <ul class="admin-kv">
          <li><span>From address</span><strong><?= e($mailFrom !== '' ? $mailFrom : '(not set)') ?></strong></li>
          <li><span>API key</span><strong><?= $hasKey ? 'set ✓' : 'not set' ?></strong></li>
        </ul>
        <form method="post" action="/admin/settings.php">
          <?= csrf_field() ?>
          <button class="pill" type="submit" name="send_test" value="1">Send a test alert</button>
        </form>
        <p class="muted tiny">The API key is a secret and stays in your server config — it is never shown or editable here.</p>
      </div>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../../app/views/footer.php'; ?>
