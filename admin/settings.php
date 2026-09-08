<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';
require_once dirname(__DIR__) . '/includes/content.php';

$keys = [
    'phone_1' => 'Phone 1',
    'phone_2' => 'Phone 2',
    'whatsapp' => 'WhatsApp number (digits, with country code)',
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
admin_header('Settings', 'settings.php');
?>
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
