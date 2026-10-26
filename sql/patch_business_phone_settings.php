<?php
/**
 * Update site settings to the current business mobile (run once on production after deploy).
 *   php sql/patch_business_phone_settings.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = db();
$upsert = $pdo->prepare(
    'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
);

$upsert->execute(['phone_1', '+91-8368142191']);
$upsert->execute(['phone_2', '']);
$upsert->execute(['whatsapp', '918368142191']);

echo "OK: phone_1=+91-8368142191, phone_2 cleared, whatsapp=918368142191\n";
