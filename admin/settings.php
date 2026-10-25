<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';
require_once dirname(__DIR__) . '/includes/content.php';
require_once dirname(__DIR__) . '/includes/public_contact.php';
require_once dirname(__DIR__) . '/includes/external_messaging.php';

$keys = [
    'phone_1' => 'Phone 1',
    'phone_2' => 'Phone 2',
    'whatsapp' => 'WhatsApp (digits for wa.me, e.g. 91836…) — or set WHATSAPP_PUBLIC_NUMBER in env',
    'email' => 'Email',
    'address' => 'Address',
    'footer_blurb' => 'Footer blurb',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Invalid CSRF token.');
        header('Location: settings.php');
        exit;
    }
    $stmt = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    foreach ($keys as $key => $label) {
        $stmt->execute([$key, trim($_POST[$key] ?? '')]);
    }
    flash_set('success', 'Settings saved.');
    header('Location: settings.php');
    exit;
}

$current = get_settings();
$waMeDigits = public_whatsapp_chat_digits();
$extCfg = external_messaging_config();
admin_header('Settings', 'settings.php');
?>
<div class="panel" style="margin-bottom:1rem;padding:1rem;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;">
  <p><strong>Public WhatsApp link (wa.me only)</strong></p>
  <p class="muted">Meta Cloud API and in-app WhatsApp were removed. Inbound chat and templates run on your <strong>external messaging platform</strong>.</p>
  <ul style="margin:0.5rem 0 0 1rem;">
    <li>Resolved <code>wa.me</code> digits: <strong><?= e($waMeDigits) ?></strong>
      <?php if ($waMeDigits !== ''): ?>
      (<a href="https://wa.me/<?= e($waMeDigits) ?>" target="_blank" rel="noopener">test link</a>)
      <?php endif; ?>
    </li>
    <li>Form → external webhook: <?= $extCfg['enabled'] && $extCfg['webhook_url'] !== '' ? '<strong>configured</strong>' : '<span class="muted">off (set EXTERNAL_MESSAGING_* in private config)</span>' ?></li>
    <?php if ($extCfg['platform_url'] !== ''): ?>
    <li>Admin dashboard link: <a href="<?= e($extCfg['platform_url']) ?>" target="_blank" rel="noopener"><?= e($extCfg['platform_url']) ?></a></li>
    <?php endif; ?>
  </ul>
  <p class="muted" style="margin-top:0.75rem;">Integration spec: <code>docs/EXTERNAL_MESSAGING_PROMPT.md</code></p>
</div>
<div class="panel">
  <form method="post" class="form-grid">
    <?= csrf_field() ?>
    <?php foreach ($keys as $key => $label): ?>
      <div>
        <label for="<?= e($key) ?>"><?= e($label) ?></label>
        <?php if ($key === 'footer_blurb' || $key === 'address'): ?>
          <textarea id="<?= e($key) ?>" name="<?= e($key) ?>"><?= e($current[$key] ?? '') ?></textarea>
        <?php else: ?>
          <input type="text" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($current[$key] ?? '') ?>">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <div><button class="btn" type="submit">Save settings</button></div>
  </form>
</div>
<?php admin_footer(); ?>
