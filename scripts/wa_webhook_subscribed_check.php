<?php
/**
 * Verify WABA is subscribed to your Meta app (Graph API).
 * Run on server: php scripts/wa_webhook_subscribed_check.php
 * Does not print access tokens.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/whatsapp_webhook_diagnostics.php';

$cfg = require dirname(__DIR__) . '/includes/config.php';
$phoneId = trim((string) ($cfg['whatsapp']['phone_number_id'] ?? ''));
echo "Configured phone_number_id: " . ($phoneId !== '' ? $phoneId : '(missing)') . PHP_EOL;

$result = whatsapp_fetch_subscribed_apps();
if (!$result['ok']) {
    fwrite(STDERR, 'Error: ' . ($result['error'] ?? 'unknown') . PHP_EOL);
    exit(1);
}

echo 'WABA ID: ' . ($result['waba_id'] ?? '') . PHP_EOL;
echo 'Subscribed apps (' . count($result['apps'] ?? []) . '):' . PHP_EOL;
foreach ($result['apps'] ?? [] as $app) {
    echo '  - id=' . ($app['id'] ?? '') . ' name=' . ($app['name'] ?? '') . PHP_EOL;
}
if (($result['apps'] ?? []) === []) {
    echo "No subscribed apps returned. In Meta Developer Dashboard: WhatsApp → Configuration → subscribe your app to the WABA." . PHP_EOL;
    exit(2);
}

echo "OK. Ensure your Meta app ID appears above and webhook callback is https://digitalcreatorss.com/api/hook-wa with field messages subscribed." . PHP_EOL;
