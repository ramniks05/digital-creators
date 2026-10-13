<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';
require_once dirname(__DIR__) . '/includes/content.php';
require_once dirname(__DIR__) . '/includes/whatsapp_public_number.php';
require_once dirname(__DIR__) . '/includes/whatsapp_config_status.php';

$keys = [
    'phone_1' => 'Phone 1',
    'phone_2' => 'Phone 2',
    'whatsapp' => 'WhatsApp fallback (digits, 91…) — leave empty if Cloud API display number is used',
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
$waStatus = whatsapp_connection_status();
$waMeDigits = whatsapp_public_chat_digits();
admin_header('Settings', 'settings.php');
?>
<div class="panel" style="margin-bottom:1rem;padding:1rem;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;">
  <p><strong>WhatsApp Cloud (Meta) — public chat number</strong></p>
  <p class="muted">Website <code>wa.me</code> buttons use this so clicks reach the same line as Admin → WhatsApp inbox.</p>
  <ul style="margin:0.5rem 0 0 1rem;">
    <li>Graph display phone: <strong><?= e((string) ($waStatus['display_phone_number'] ?? '—')) ?></strong></li>
    <li>Resolved <code>wa.me</code> digits: <strong><?= e($waMeDigits) ?></strong>
      (<a href="https://wa.me/<?= e($waMeDigits) ?>" target="_blank" rel="noopener">test link</a>)</li>
    <li>Phone Number ID: <?= e((string) ($waStatus['phone_number_id'] ?? '—')) ?></li>
  </ul>
  <?php if (empty($waStatus['graph_api_ping_ok'])): ?>
    <p class="muted" style="margin-top:0.75rem;">Cloud API ping failed — check private config token and <code>phone_number_id</code> for +91 83681 42191.</p>
  <?php endif; ?>
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
