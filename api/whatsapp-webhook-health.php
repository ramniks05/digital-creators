<?php
/**
 * Safe webhook config check (no secrets). Remove or restrict in production if desired.
 */
header('Content-Type: application/json');

$configPath = null;
$candidates = [
    dirname(__DIR__, 2) . '/private/digital_creators_config.php',
    dirname(__DIR__) . '/../private/digital_creators_config.php',
    dirname(__DIR__) . '/private/digital_creators_config.php',
    dirname(__DIR__) . '/includes/config.local.php',
];

foreach ($candidates as $path) {
    if (is_file($path) && is_readable($path)) {
        $configPath = $path;
        break;
    }
}

$full = require dirname(__DIR__) . '/includes/config.php';
$wa = $full['whatsapp'] ?? [];
$verify = trim((string) ($wa['webhook_verify_token'] ?? ''));
$secret = trim((string) ($wa['app_secret'] ?? ''));

echo json_encode([
    'ok' => $verify !== '',
    'config_file_loaded' => $configPath !== null,
    'config_hint' => $configPath ? basename(dirname($configPath)) . '/' . basename($configPath) : null,
    'webhook_verify_token_set' => $verify !== '',
    'verify_token_length' => $verify !== '' ? strlen($verify) : 0,
    'meta_app_secret_set' => $secret !== '',
    'callback_urls' => [
        'api' => 'https://digitalcreatorss.com/api/whatsapp-webhook.php',
        'webhook' => 'https://digitalcreatorss.com/webhook/whatsapp',
    ],
], JSON_PRETTY_PRINT);
